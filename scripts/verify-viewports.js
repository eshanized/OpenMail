import { chromium } from 'playwright';
import { spawn } from 'child_process';
import fs from 'fs';
import path from 'path';

const VIEWPORTS = [
    { name: 'iPhone SE 1st gen / ultra-compact', width: 320, height: 568 },
    { name: 'common Android compact', width: 360, height: 800 },
    { name: 'iPhone SE 2nd/3rd gen', width: 375, height: 667 },
    { name: 'modern mainstream iPhone', width: 390, height: 844 },
    { name: 'modern mainstream Android', width: 412, height: 915 },
    { name: 'large iPhone Pro Max', width: 430, height: 932 },
    { name: 'iPad portrait', width: 768, height: 1024 },
    { name: 'iPad landscape / small laptop', width: 1024, height: 768 },
    { name: 'desktop baseline', width: 1440, height: 900 }
];

const SCREENSHOT_DIR = path.resolve('test-results/screenshots');
fs.mkdirSync(SCREENSHOT_DIR, { recursive: true });

async function waitForServer(url, timeoutMs = 15000) {
    const startTime = Date.now();
    while (Date.now() - startTime < timeoutMs) {
        try {
            const res = await fetch(url);
            if (res.ok || res.status === 302 || res.status === 200) {
                return true;
            }
        } catch (e) {
            // Wait and retry
        }
        await new Promise(r => setTimeout(r, 300));
    }
    throw new Error(`Server at ${url} failed to start within ${timeoutMs}ms`);
}

async function run() {
    console.log('🚀 Starting OpenMail Playwright Verification across 9 Viewports...');

    // 1. Seed test folder cache via tinker
    const seedProcess = spawn('php', ['artisan', 'tinker', '--execute', `
        Cache::put('folders:1', [
            ['path' => 'INBOX', 'name' => 'Inbox', 'role' => 'inbox', 'total' => 12, 'total_count' => 12, 'unread' => 3, 'unread_count' => 3, 'attributes' => []],
            ['path' => 'Sent', 'name' => 'Sent', 'role' => 'sent', 'total' => 5, 'total_count' => 5, 'unread' => 0, 'unread_count' => 0, 'attributes' => []],
            ['path' => 'Drafts', 'name' => 'Drafts', 'role' => 'drafts', 'total' => 1, 'total_count' => 1, 'unread' => 0, 'unread_count' => 0, 'attributes' => []],
            ['path' => 'Trash', 'name' => 'Trash', 'role' => 'trash', 'total' => 0, 'total_count' => 0, 'unread' => 0, 'unread_count' => 0, 'attributes' => []],
        ], now()->addHours(24));
    `]);
    await new Promise(r => seedProcess.on('close', r));

    // 2. Start PHP built-in server on port 8123
    const server = spawn('php', ['-S', '127.0.0.1:8123', '-t', 'public'], {
        stdio: 'ignore'
    });

    const cleanup = () => {
        try {
            server.kill('SIGTERM');
        } catch (e) {}
    };

    process.on('exit', cleanup);
    process.on('SIGINT', () => { cleanup(); process.exit(1); });

    try {
        await waitForServer('http://127.0.0.1:8123/up');
        console.log('✅ PHP Test Server running at http://127.0.0.1:8123');

        // 3. Launch Chromium
        const browser = await chromium.launch({
            executablePath: '/usr/sbin/chromium',
            args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-dev-shm-usage']
        });

        const report = {
            timestamp: new Date().toISOString(),
            viewportsTested: VIEWPORTS.length,
            results: []
        };

        for (const vp of VIEWPORTS) {
            console.log(`\n📱 Testing Viewport: ${vp.name} (${vp.width}x${vp.height})`);
            const context = await browser.newContext({
                viewport: { width: vp.width, height: vp.height },
                userAgent: 'Mozilla/5.0 (Playwright Test Runner) AppleWebKit/537.36'
            });

            const page = await context.newPage();
            const vpResult = {
                viewport: vp,
                pages: {},
                tapTargetsValid: true,
                passed: true
            };

            // --- A. Login Screen (Light & Dark) ---
            await page.goto('http://127.0.0.1:8123/login', { waitUntil: 'networkidle' });
            await page.waitForTimeout(300);

            // Light mode overflow check
            let metrics = await page.evaluate(() => ({
                scrollWidth: document.documentElement.scrollWidth,
                innerWidth: window.innerWidth,
                hasOverflow: document.documentElement.scrollWidth > window.innerWidth
            }));
            vpResult.pages.loginLight = {
                scrollWidth: metrics.scrollWidth,
                innerWidth: metrics.innerWidth,
                overflow: metrics.hasOverflow
            };
            if (metrics.hasOverflow) vpResult.passed = false;
            await page.screenshot({ path: path.join(SCREENSHOT_DIR, `login-${vp.width}x${vp.height}-light.png`) });

            // Dark mode toggle & check
            await page.evaluate(() => {
                localStorage.setItem('theme', 'dark');
                document.documentElement.classList.add('dark');
            });
            await page.waitForTimeout(100);
            metrics = await page.evaluate(() => ({
                scrollWidth: document.documentElement.scrollWidth,
                innerWidth: window.innerWidth,
                hasOverflow: document.documentElement.scrollWidth > window.innerWidth
            }));
            vpResult.pages.loginDark = {
                scrollWidth: metrics.scrollWidth,
                innerWidth: metrics.innerWidth,
                overflow: metrics.hasOverflow
            };
            if (metrics.hasOverflow) vpResult.passed = false;
            await page.screenshot({ path: path.join(SCREENSHOT_DIR, `login-${vp.width}x${vp.height}-dark.png`) });

            // Restore light mode for consistency
            await page.evaluate(() => {
                localStorage.setItem('theme', 'light');
                document.documentElement.classList.remove('dark');
            });

            // Perform Login
            await page.fill('#email', 'bins.ethelyn@example.net');
            await page.fill('#password', 'password');
            await Promise.all([
                page.waitForNavigation({ timeout: 10000 }).catch(() => {}),
                page.click('button[type="submit"]')
            ]);
            await page.waitForTimeout(500);

            // --- B. Mailbox / Inbox Screen ---
            if (!page.url().includes('/mailbox')) {
                await page.goto('http://127.0.0.1:8123/mailbox', { waitUntil: 'networkidle' });
            }
            await page.waitForTimeout(400);

            metrics = await page.evaluate(() => ({
                scrollWidth: document.documentElement.scrollWidth,
                innerWidth: window.innerWidth,
                hasOverflow: document.documentElement.scrollWidth > window.innerWidth
            }));
            vpResult.pages.mailbox = {
                scrollWidth: metrics.scrollWidth,
                innerWidth: metrics.innerWidth,
                overflow: metrics.hasOverflow
            };
            if (metrics.hasOverflow) vpResult.passed = false;

            // Check tap target sizes on header / actions
            const tapTargetCheck = await page.evaluate(() => {
                const buttons = Array.from(document.querySelectorAll('header button, header a'));
                const smallButtons = buttons.filter(b => {
                    const rect = b.getBoundingClientRect();
                    // Primary interaction buttons should have decent height/width (at least 32px for compact icons, ~40+ for main buttons)
                    return rect.width > 0 && rect.height > 0 && (rect.width < 28 || rect.height < 28);
                });
                return smallButtons.length === 0;
            });
            vpResult.tapTargetsValid = tapTargetCheck;

            // Test Mobile Sidebar Drawer if on mobile/tablet (< 1024)
            if (vp.width < 1024) {
                const sidebarBtn = page.locator('button[aria-label="Toggle navigation menu"]:visible, button[aria-label="Open sidebar"]:visible').first();
                if (await sidebarBtn.count() > 0) {
                    await sidebarBtn.click({ timeout: 5000 }).catch(() => {});
                    await page.waitForTimeout(400);
                    const sidebarMetrics = await page.evaluate(() => ({
                        scrollWidth: document.documentElement.scrollWidth,
                        innerWidth: window.innerWidth,
                        hasOverflow: document.documentElement.scrollWidth > window.innerWidth
                    }));
                    if (sidebarMetrics.hasOverflow) vpResult.passed = false;
                    await page.screenshot({ path: path.join(SCREENSHOT_DIR, `sidebar-drawer-${vp.width}x${vp.height}.png`) });

                    // Close sidebar with Escape
                    await page.keyboard.press('Escape');
                    await page.waitForTimeout(300);
                }
            }

            await page.screenshot({ path: path.join(SCREENSHOT_DIR, `inbox-${vp.width}x${vp.height}.png`) });

            // --- C. Composer Modal ---
            const composeBtn = page.locator('button:visible', { hasText: 'Compose' }).first();
            if (await composeBtn.count() > 0) {
                await composeBtn.click({ timeout: 5000 }).catch(async () => {
                    await page.evaluate(() => { if (typeof Livewire !== 'undefined') Livewire.dispatch('openComposer', { mode: 'compose' }); });
                });
                await page.waitForTimeout(600);
                const composerMetrics = await page.evaluate(() => ({
                    scrollWidth: document.documentElement.scrollWidth,
                    innerWidth: window.innerWidth,
                    hasOverflow: document.documentElement.scrollWidth > window.innerWidth
                }));
                vpResult.pages.composer = {
                    scrollWidth: composerMetrics.scrollWidth,
                    innerWidth: composerMetrics.innerWidth,
                    overflow: composerMetrics.hasOverflow
                };
                if (composerMetrics.hasOverflow) vpResult.passed = false;
                await page.screenshot({ path: path.join(SCREENSHOT_DIR, `composer-${vp.width}x${vp.height}.png`) });

                // Close composer
                const closeBtn = page.locator('button[title*="Close"]:visible, button[title*="Esc"]:visible').first();
                if (await closeBtn.count() > 0) {
                    await closeBtn.click().catch(() => {});
                } else {
                    await page.keyboard.press('Escape');
                }
                await page.waitForTimeout(300);
            }

            // --- D. Search Page ---
            await page.goto('http://127.0.0.1:8123/search?q=meeting', { waitUntil: 'networkidle' });
            await page.waitForTimeout(300);
            metrics = await page.evaluate(() => ({
                scrollWidth: document.documentElement.scrollWidth,
                innerWidth: window.innerWidth,
                hasOverflow: document.documentElement.scrollWidth > window.innerWidth
            }));
            vpResult.pages.search = {
                scrollWidth: metrics.scrollWidth,
                innerWidth: metrics.innerWidth,
                overflow: metrics.hasOverflow
            };
            if (metrics.hasOverflow) vpResult.passed = false;

            // If mobile, test opening mobile filter drawer
            if (vp.width < 1024) {
                const filterBtn = page.locator('button[aria-label="Open search filters"]:visible').first();
                if (await filterBtn.count() > 0) {
                    await filterBtn.click({ timeout: 5000 }).catch(() => {});
                    await page.waitForTimeout(400);
                    const filterDrawerMetrics = await page.evaluate(() => ({
                        scrollWidth: document.documentElement.scrollWidth,
                        innerWidth: window.innerWidth,
                        hasOverflow: document.documentElement.scrollWidth > window.innerWidth
                    }));
                    if (filterDrawerMetrics.hasOverflow) vpResult.passed = false;
                    await page.screenshot({ path: path.join(SCREENSHOT_DIR, `search-filter-drawer-${vp.width}x${vp.height}.png`) });
                    await page.keyboard.press('Escape');
                    await page.waitForTimeout(300);
                }
            }
            await page.screenshot({ path: path.join(SCREENSHOT_DIR, `search-${vp.width}x${vp.height}.png`) });

            // --- E. Settings Page ---
            await page.goto('http://127.0.0.1:8123/settings', { waitUntil: 'networkidle' });
            await page.waitForTimeout(300);
            metrics = await page.evaluate(() => ({
                scrollWidth: document.documentElement.scrollWidth,
                innerWidth: window.innerWidth,
                hasOverflow: document.documentElement.scrollWidth > window.innerWidth
            }));
            vpResult.pages.settings = {
                scrollWidth: metrics.scrollWidth,
                innerWidth: metrics.innerWidth,
                overflow: metrics.hasOverflow
            };
            if (metrics.hasOverflow) vpResult.passed = false;
            await page.screenshot({ path: path.join(SCREENSHOT_DIR, `settings-${vp.width}x${vp.height}.png`) });

            report.results.push(vpResult);
            console.log(`   Login: scrollWidth=${vpResult.pages.loginLight?.scrollWidth}px (inner: ${vp.width}px) - ${vpResult.pages.loginLight?.overflow ? '❌ OVERFLOW' : '✅ No overflow'}`);
            console.log(`   Inbox: scrollWidth=${vpResult.pages.mailbox?.scrollWidth}px (inner: ${vp.width}px) - ${vpResult.pages.mailbox?.overflow ? '❌ OVERFLOW' : '✅ No overflow'}`);
            if (vpResult.pages.composer) {
                console.log(`   Composer: scrollWidth=${vpResult.pages.composer?.scrollWidth}px (inner: ${vp.width}px) - ${vpResult.pages.composer?.overflow ? '❌ OVERFLOW' : '✅ No overflow'}`);
            }
            console.log(`   Search: scrollWidth=${vpResult.pages.search?.scrollWidth}px (inner: ${vp.width}px) - ${vpResult.pages.search?.overflow ? '❌ OVERFLOW' : '✅ No overflow'}`);
            console.log(`   Settings: scrollWidth=${vpResult.pages.settings?.scrollWidth}px (inner: ${vp.width}px) - ${vpResult.pages.settings?.overflow ? '❌ OVERFLOW' : '✅ No overflow'}`);
            console.log(`   Overall: ${vpResult.passed ? '🎉 PASSED' : '⚠️ FAILED'}`);

            await context.close();
        }

        await browser.close();

        // Save detailed report
        fs.writeFileSync(path.resolve('test-results/viewport-verification-report.json'), JSON.stringify(report, null, 2));
        console.log('\n📄 Report written to test-results/viewport-verification-report.json');

        const allPassed = report.results.every(r => r.passed);
        console.log(`\n========================================`);
        console.log(`FINAL RESULT: ${allPassed ? '✅ ALL 9 VIEWPORTS PASSED WITH ZERO HORIZONTAL OVERFLOW' : '❌ SOME VIEWPORTS FAILED'}`);
        console.log(`========================================\n`);

    } finally {
        cleanup();
    }
}

run().catch(err => {
    console.error('Test execution failed:', err);
    process.exit(1);
});
