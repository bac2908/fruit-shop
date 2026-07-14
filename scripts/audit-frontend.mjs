import AxeBuilder from '@axe-core/playwright';
import { chromium } from '@playwright/test';
import { mkdir, writeFile } from 'node:fs/promises';
import path from 'node:path';

const targetUrl = process.env.APP_AUDIT_URL
    ?? 'http://localhost/webtraicay_php/fruitshop/public/';
const outputDirectory = path.resolve('storage/app/test-artifacts/frontend');
const profiles = [
    { name: 'desktop', viewport: { width: 1440, height: 900 } },
    { name: 'mobile', viewport: { width: 390, height: 844 }, isMobile: true },
];

await mkdir(outputDirectory, { recursive: true });

const browser = await chromium.launch({ headless: true });
const report = {
    generatedAt: new Date().toISOString(),
    targetUrl,
    profiles: {},
};

for (const profile of profiles) {
    const context = await browser.newContext({
        viewport: profile.viewport,
        isMobile: profile.isMobile ?? false,
        locale: 'vi-VN',
        reducedMotion: 'reduce',
    });
    const page = await context.newPage();
    const consoleErrors = [];
    const failedRequests = [];

    page.on('console', (message) => {
        if (message.type() === 'error') consoleErrors.push(message.text());
    });
    page.on('pageerror', (error) => consoleErrors.push(error.message));
    page.on('requestfailed', (request) => {
        failedRequests.push({
            url: request.url(),
            reason: request.failure()?.errorText ?? 'unknown',
        });
    });

    const response = await page.goto(targetUrl, {
        waitUntil: 'domcontentloaded',
        timeout: 45_000,
    });
    await page.waitForTimeout(2500);

    const pageState = await page.evaluate(() => {
        const brokenImages = [...document.images]
            .filter((image) => image.complete && image.naturalWidth === 0)
            .map((image) => image.currentSrc || image.src)
            .slice(0, 25);
        const overflowingElements = [...document.querySelectorAll('body *')]
            .filter((element) => {
                if (element.closest('.owl-stage-outer') || element.closest('nav .nav-left')) {
                    return false;
                }

                const rect = element.getBoundingClientRect();
                return rect.right > document.documentElement.clientWidth + 1 || rect.left < -1;
            })
            .map((element) => ({
                tag: element.tagName.toLowerCase(),
                className: typeof element.className === 'string' ? element.className : '',
                left: Math.round(element.getBoundingClientRect().left),
                right: Math.round(element.getBoundingClientRect().right),
            }))
            .slice(0, 25);
        const resources = performance.getEntriesByType('resource');

        return {
            title: document.title,
            h1Count: document.querySelectorAll('h1').length,
            bodyWidth: document.body.scrollWidth,
            viewportWidth: document.documentElement.clientWidth,
            brokenImages,
            overflowingElements,
            resourceCount: resources.length,
            transferredBytes: resources.reduce((total, resource) => total + (resource.transferSize || 0), 0),
            imageCount: document.images.length,
            lazyImageCount: document.querySelectorAll('img[loading="lazy"]').length,
        };
    });

    const accessibility = await new AxeBuilder({ page })
        .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'])
        .analyze();

    await page.evaluate(async () => {
        const step = Math.max(window.innerHeight * 0.8, 320);
        for (let offset = 0; offset < document.documentElement.scrollHeight; offset += step) {
            window.scrollTo(0, offset);
            await new Promise((resolve) => setTimeout(resolve, 40));
        }
        window.scrollTo(0, 0);
    });
    await page.waitForTimeout(500);

    await page.screenshot({
        path: path.join(outputDirectory, `${profile.name}.png`),
        fullPage: true,
    });

    report.profiles[profile.name] = {
        status: response?.status() ?? null,
        pageState,
        consoleErrors: [...new Set(consoleErrors)].slice(0, 25),
        failedRequests: failedRequests.slice(0, 25),
        accessibility: {
            violationCount: accessibility.violations.length,
            seriousOrCritical: accessibility.violations
                .filter((violation) => ['serious', 'critical'].includes(violation.impact))
                .map((violation) => ({
                    id: violation.id,
                    impact: violation.impact,
                    help: violation.help,
                    nodes: violation.nodes.length,
                })),
            violations: accessibility.violations.map((violation) => ({
                id: violation.id,
                impact: violation.impact,
                help: violation.help,
                nodes: violation.nodes.length,
                examples: violation.nodes.slice(0, 10).map((node) => ({
                    target: node.target,
                    html: node.html,
                    failureSummary: node.failureSummary,
                })),
            })),
        },
    };

    await context.close();
}

await browser.close();
await writeFile(
    path.join(outputDirectory, 'report.json'),
    `${JSON.stringify(report, null, 2)}\n`,
    'utf8',
);

console.log(JSON.stringify(report, null, 2));

const failed = Object.values(report.profiles).some((profile) => (
    profile.status !== 200
    || profile.pageState.brokenImages.length > 0
    || profile.pageState.bodyWidth > profile.pageState.viewportWidth + 1
    || profile.accessibility.seriousOrCritical.length > 0
));

process.exitCode = failed ? 1 : 0;
