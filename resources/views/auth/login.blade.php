<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#cf4234">
    <meta name="application-name" content="{{ config('app.name', 'OpenMail') }}">
    <title>{{ config('app.name', 'OpenMail') }} — Sign in</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script @cspNonceAttribute>
        (function() {
            try {
                var theme = localStorage.getItem('theme') || 'system';
                var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                if (theme === 'dark' || (theme === 'system' && prefersDark)) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
                document.documentElement.classList.add('density-' + (localStorage.getItem('density') || 'regular'));
                document.documentElement.dataset.themeInitialized = 'true';
            } catch (error) {
                document.documentElement.dataset.themeInitialized = 'true';
            }
        })();
    </script>
</head>
<body class="auth-body bg-surface antialiased">
    <main class="auth-layout min-h-[100dvh] flex">
        <section class="auth-brand-panel hidden lg:flex lg:w-[44%] xl:w-[46%] flex-col justify-between p-10 xl:p-14" aria-label="About OpenMail">
            <a href="/" class="auth-brand-lockup inline-flex items-center gap-3 w-fit" aria-label="OpenMail home">
                <span class="auth-brand-mark" aria-hidden="true">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </span>
                <span class="text-base font-semibold tracking-tight text-ink">{{ config('app.name', 'OpenMail') }}</span>
            </a>

            <div class="auth-brand-content">
                <span class="auth-eyebrow"><span class="auth-eyebrow-dot"></span> PRIVATE BY DESIGN</span>
                <h1 class="auth-brand-title">A calmer place<br>for <span>your email.</span></h1>
                <p class="auth-brand-description">All your conversations, thoughtfully organized. Connect to your existing mail server and get a focused workspace that stays yours.</p>

                <div class="auth-feature-list">
                    <div class="auth-feature-item">
                        <span class="auth-feature-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3l7 3v5c0 4.5-3 7.5-7 10-4-2.5-7-5.5-7-10V6l7-3z"/><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4"/></svg>
                        </span>
                        <span><strong>Your infrastructure</strong><small>Self-hosted and connected to your existing IMAP/SMTP server.</small></span>
                    </div>
                    <div class="auth-feature-item">
                        <span class="auth-feature-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3" y="5" width="18" height="14" rx="2"/><path stroke-linecap="round" stroke-linejoin="round" d="M3 7l9 6 9-6"/></svg>
                        </span>
                        <span><strong>Everything in its place</strong><small>Threads, folders, labels, and search in one uncluttered inbox.</small></span>
                    </div>
                    <div class="auth-feature-item">
                        <span class="auth-feature-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="M3 12h18M12 3a14 14 0 010 18M12 3a14 14 0 000 18"/></svg>
                        </span>
                        <span><strong>No third-party AI analysis</strong><small>Your mail content is not sent to external AI services.</small></span>
                    </div>
                </div>

                <div class="auth-preview" aria-hidden="true">
                    <div class="auth-preview-top">
                        <span class="auth-preview-dots"><i></i><i></i><i></i></span>
                        <span class="auth-preview-caption">Your workspace</span>
                        <span class="auth-preview-status"><span></span> Private</span>
                    </div>
                    <div class="auth-preview-search"><span class="auth-preview-search-icon"></span> Search your mail</div>
                    <div class="auth-preview-mail">
                        <span class="auth-preview-avatar auth-preview-avatar-coral">A</span>
                        <span class="auth-preview-mail-copy"><strong>Alex Morgan</strong><span>Project update and next steps</span></span>
                        <span class="auth-preview-time">09:41</span>
                    </div>
                    <div class="auth-preview-mail">
                        <span class="auth-preview-avatar auth-preview-avatar-slate">S</span>
                        <span class="auth-preview-mail-copy"><strong>Sarah Chen</strong><span>Re: Design review</span></span>
                        <span class="auth-preview-time">Yesterday</span>
                    </div>
                </div>
            </div>

            <p class="auth-brand-footer">OpenMail <span>·</span> by Tonmoy Infrastructure &amp; Vision</p>
        </section>

        <section class="auth-main flex-1 flex flex-col items-center justify-center px-4 py-8 sm:px-8 lg:px-12" aria-label="Sign in">
            <div class="auth-mobile-brand lg:hidden">
                <span class="auth-brand-mark" aria-hidden="true">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </span>
                <span>{{ config('app.name', 'OpenMail') }}</span>
            </div>

            <div class="auth-card w-full bg-surface-raised border border-border">
                <div class="auth-card-heading">
                    <span class="auth-card-kicker">WELCOME BACK</span>
                    <h2>Sign in to your inbox</h2>
                    <p>Use your OpenMail account to continue.</p>
                </div>

                @livewire('login-form')

                <div class="auth-security-note">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3l7 3v5c0 4.5-3 7.5-7 10-4-2.5-7-5.5-7-10V6l7-3z"/></svg>
                    <span>Your sign-in is handled by your own OpenMail server.</span>
                </div>
            </div>

            <footer class="auth-main-footer">
                <span>Secure, self-hosted webmail</span>
                <span class="auth-footer-separator">·</span>
                <span>Tonmoy Infrastructure &amp; Vision</span>
            </footer>
        </section>
    </main>
</body>
</html>
