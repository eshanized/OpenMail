<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\MailTransportService;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Mockery;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;
use Tests\TestCase;

class MailTransportServiceTest extends TestCase
{
    public function test_custom_transport_can_be_set_and_retrieved(): void
    {
        $transport = Mockery::mock(TransportInterface::class);
        $service = new MailTransportService;

        $this->assertSame($service, $service->setTransport($transport));
        $this->assertSame($transport, $service->getTransport());
    }

    public function test_addresses_are_parsed_from_comma_and_semicolon_separated_values(): void
    {
        $service = new MailTransportService;

        $this->assertSame([], $service->parseAddresses('  '));

        $addresses = $service->parseAddresses(
            'Alice Example <alice@example.test>, bob@example.test; "Carol Example" <carol@example.test>, invalid'
        );

        $this->assertSame(
            ['alice@example.test', 'bob@example.test', 'carol@example.test'],
            array_map(fn ($address) => $address->getAddress(), $addresses)
        );
        $this->assertSame('Alice Example', $addresses[0]->getName());
        $this->assertSame('Carol Example', $addresses[2]->getName());
    }

    public function test_canonical_email_includes_sender_recipients_body_and_thread_headers(): void
    {
        $user = new User(['name' => 'Mail Sender', 'email' => 'sender@example.test']);
        $service = new MailTransportService;

        $email = $service->buildCanonicalEmail([
            'to' => 'Receiver <receiver@example.test>',
            'cc' => 'copy@example.test',
            'bcc' => 'hidden@example.test',
            'subject' => 'A subject 😀',
            'body' => '<p>Hello &amp; welcome</p>',
            'in_reply_to' => '<parent@example.test>',
            'references' => '<root@example.test> <parent@example.test>',
        ], $user, '<stable-id@example.test>');

        $this->assertSame('sender@example.test', $email->getFrom()[0]->getAddress());
        $this->assertSame('receiver@example.test', $email->getTo()[0]->getAddress());
        $this->assertSame('copy@example.test', $email->getCc()[0]->getAddress());
        $this->assertSame('hidden@example.test', $email->getBcc()[0]->getAddress());
        $this->assertSame('A subject 😀', $email->getSubject());
        $this->assertSame('Hello & welcome', trim($email->getTextBody()));
        $this->assertSame('<p>Hello &amp; welcome</p>', $email->getHtmlBody());
        $this->assertSame(['stable-id@example.test'], $email->getHeaders()->get('Message-ID')->getIds());
        $this->assertSame(['parent@example.test'], $email->getHeaders()->get('In-Reply-To')->getIds());
        $this->assertSame(['root@example.test', 'parent@example.test'], $email->getHeaders()->get('References')->getIds());
    }

    public function test_empty_optional_fields_use_safe_defaults_and_plain_text_conversion(): void
    {
        $service = new MailTransportService;
        $email = $service->buildCanonicalEmail(['body' => '<p>One</p><p>Two<br>Three</p>'], null);

        $this->assertSame('(no subject)', $email->getSubject());
        $this->assertStringContainsString('openmail.local', $email->getHeaders()->get('Message-ID')->getIds()[0]);
        $this->assertSame("One\n\nTwo\nThree", $email->getTextBody());
        $this->assertSame("One\n\nTwo\nThree", $service->htmlToText('<p>One</p><p>Two<br>Three</p>'));
    }

    public function test_existing_attachments_are_added_and_missing_paths_are_ignored(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'openmail-attachment-');
        file_put_contents($path, 'sample attachment');

        try {
            $email = (new MailTransportService)->buildCanonicalEmail([
                'attachments' => [
                    ['path' => $path, 'name' => 'sample.txt', 'mime' => 'text/plain'],
                    ['path' => $path.'.missing', 'name' => 'missing.txt'],
                ],
            ], null);

            $this->assertCount(1, $email->getAttachments());
            $this->assertSame('sample.txt', $email->getAttachments()[0]->getFilename());
        } finally {
            unlink($path);
        }
    }

    public function test_envelope_recipients_include_bcc_and_remove_duplicates(): void
    {
        $service = new MailTransportService;
        $email = $service->buildCanonicalEmail([
            'to' => 'to@example.test',
            'cc' => 'cc@example.test',
            'bcc' => 'blind@example.test',
        ], null);

        $recipients = $service->getEnvelopeRecipients($email, ['blind@example.test', 'extra@example.test']);

        $this->assertSame(
            ['to@example.test', 'cc@example.test', 'blind@example.test', 'extra@example.test'],
            array_map(fn ($address) => $address->getAddress(), $recipients)
        );
    }

    public function test_deliver_returns_success_and_preserves_stable_message_id(): void
    {
        $transport = Mockery::mock(TransportInterface::class);
        $transport->shouldReceive('send')->once()->with(Mockery::type(Email::class))->andReturn(null);
        $service = new MailTransportService($transport);
        $email = $service->buildCanonicalEmail(['to' => 'to@example.test', 'subject' => 'Hello'], null, 'delivery-id@example.test');
        $email->from('sender@example.test');

        $result = $service->deliver($email);

        $this->assertTrue($result['success']);
        $this->assertSame('delivery-id@example.test', $result['message_id']);
        $this->assertNull($result['category']);
        $this->assertNull($result['error']);
        $this->assertFalse($result['uncertain']);
        $this->assertStringContainsString('Subject: Hello', $result['mime_string']);
    }

    public function test_transport_rejections_are_classified_as_rejected(): void
    {
        $transport = Mockery::mock(TransportInterface::class);
        $transport->shouldReceive('send')->once()->andThrow(new TransportException('550 recipient rejected'));
        Log::shouldReceive('warning')->once();

        $service = new MailTransportService($transport);
        $result = $service->deliver((new MailTransportService)->buildCanonicalEmail(['to' => 'to@example.test'], null)->from('sender@example.test'));

        $this->assertFalse($result['success']);
        $this->assertSame('rejection', $result['category']);
        $this->assertFalse($result['uncertain']);
    }

    public function test_transport_failure_after_message_body_is_marked_uncertain(): void
    {
        $transport = Mockery::mock(TransportInterface::class);
        $transport->shouldReceive('send')->once()->andThrow(new TransportException('Connection timed out after sending message body'));
        Log::shouldReceive('warning')->once();

        $service = new MailTransportService($transport);
        $result = $service->deliver((new MailTransportService)->buildCanonicalEmail(['to' => 'to@example.test'], null)->from('sender@example.test'));

        $this->assertFalse($result['success']);
        $this->assertSame('uncertain', $result['category']);
        $this->assertTrue($result['uncertain']);
    }

    public function test_unexpected_delivery_errors_are_reported_as_rejections(): void
    {
        $transport = Mockery::mock(TransportInterface::class);
        $transport->shouldReceive('send')->once()->andThrow(new \RuntimeException('unexpected adapter failure'));
        Log::shouldReceive('error')->once();

        $service = new MailTransportService($transport);
        $result = $service->deliver((new MailTransportService)->buildCanonicalEmail(['to' => 'to@example.test'], null)->from('sender@example.test'));

        $this->assertFalse($result['success']);
        $this->assertSame('rejection', $result['category']);
        $this->assertSame('unexpected adapter failure', $result['error']);
        $this->assertFalse($result['uncertain']);
    }

    public function test_send_mime_rejects_empty_mime_and_missing_sender(): void
    {
        $service = new MailTransportService;

        try {
            $service->sendMime('  ');
            $this->fail('Empty MIME content must be rejected.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('non-empty MIME', $exception->getMessage());
        }

        try {
            $service->sendMime("Subject: Hello\n\nBody");
            $this->fail('A persisted MIME message must have a sender.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('sender is required', $exception->getMessage());
        }
    }

    public function test_raw_mime_without_envelope_recipients_is_rejected(): void
    {
        $user = new User(['name' => 'Mail Sender', 'email' => 'sender@example.test']);
        $result = (new MailTransportService)->sendMime(
            "Message-ID: <raw-id@example.test>\r\nSubject: retained\r\n\r\nBody",
            $user,
            []
        );

        $this->assertFalse($result['success']);
        $this->assertSame('rejection', $result['category']);
        $this->assertSame('raw-id@example.test', $result['message_id']);
        $this->assertSame('The message has no valid envelope recipients.', $result['error']);
        $this->assertFalse($result['uncertain']);
    }

    public function test_raw_mime_delivery_uses_envelope_recipients_without_rewriting_the_mime(): void
    {
        $user = new User(['name' => 'Mail Sender', 'email' => 'sender@example.test']);
        $rawMime = "Message-ID: <raw-id@example.test>\r\nSubject: retained\r\n\r\nBody";
        $transport = Mockery::mock(TransportInterface::class);
        $transport->shouldReceive('send')->once()->with(
            Mockery::on(fn ($message) => $message instanceof RawMessage && $message->toString() === $rawMime),
            Mockery::on(function ($envelope): bool {
                if ($envelope instanceof Envelope === false) {
                    return false;
                }

                return array_map(fn ($address) => $address->getAddress(), $envelope->getRecipients())
                    === ['to@example.test', 'blind@example.test'];
            })
        )->andReturn(null);

        $result = (new MailTransportService($transport))->sendMime($rawMime, $user, [
            'to' => 'to@example.test',
            'bcc' => 'blind@example.test, blind@example.test',
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame('raw-id@example.test', $result['message_id']);
        $this->assertSame($rawMime, $result['mime_string']);
    }
}
