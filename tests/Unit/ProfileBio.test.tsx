/// <reference types="node" />
import assert from 'node:assert/strict';
import { renderToStaticMarkup } from 'react-dom/server';
import { test } from 'vite-plus/test';
import { ProfileBio } from '../../resources/js/components/profile-bio';

for (const bio of [null, '']) {
    test(`omits an empty bio: ${bio}`, () => {
        assert.equal(renderToStaticMarkup(<ProfileBio bio={bio} />), '');
    });
}

test('preserves plain text, mentions and line breaks in a bio', () => {
    const bio = 'Building with @laravel.\nEmail hello@example.com';
    const markup = renderToStaticMarkup(<ProfileBio bio={bio} />);

    assert.equal(markup.replace(/<[^>]+>/g, ''), bio);
    assert.ok(markup.includes('whitespace-pre-wrap'));
    assert.ok(!markup.includes('<a'));
});

test('highlights explicit URLs and bare domains with safe external links', () => {
    const markup = renderToStaticMarkup(
        <ProfileBio bio="Building https://example.com/docs and laravel.com." />,
    );

    assert.equal((markup.match(/<a /g) ?? []).length, 2);
    for (const href of ['https://example.com/docs', 'https://laravel.com']) {
        assert.ok(markup.includes(`href="${href}"`));
    }
    assert.equal((markup.match(/target="_blank"/g) ?? []).length, 2);
    assert.equal(
        (markup.match(/rel="noopener noreferrer nofollow ugc"/g) ?? []).length,
        2,
    );
    assert.equal(
        (markup.match(/text-primary hover:underline/g) ?? []).length,
        2,
    );
    assert.ok(markup.endsWith('</a>.</p>'));
});

test('escapes HTML and leaves unsafe schemes as text', () => {
    const markup = renderToStaticMarkup(
        <ProfileBio bio="<script>alert(1)</script> javascript:alert(1)" />,
    );

    assert.ok(markup.includes('&lt;script&gt;alert(1)&lt;/script&gt;'));
    assert.ok(!markup.includes('<script'));
    assert.ok(!markup.includes('<a'));
});

for (const [text, href] of [
    ['example.com', 'https://example.com'],
    ['www.example.com', 'https://www.example.com'],
    ['http://example.com', 'http://example.com'],
    ['https://example.com', 'https://example.com'],
    ['http://www.example.com', 'http://www.example.com'],
    ['https://www.example.com', 'https://www.example.com'],
    ['HTTPS://example.com', 'HTTPS://example.com'],
    ['example.dev', 'https://example.dev'],
    ['example.io', 'https://example.io'],
    ['example.co.uk', 'https://example.co.uk'],
    ['www.example.com/about', 'https://www.example.com/about'],
    ['example.com?q=hello#bio', 'https://example.com?q=hello#bio'],
    ['https://dev-null.sh', 'https://dev-null.sh'],
    ['http://localhost:8000', 'http://localhost:8000'],
    ['https://example.com/docs_(guide)', 'https://example.com/docs_(guide)'],
]) {
    test(`links a bio URL written as ${text}`, () => {
        const markup = renderToStaticMarkup(
            <ProfileBio bio={`See (${text}).`} />,
        );

        assert.equal((markup.match(/<a /g) ?? []).length, 1);
        assert.ok(markup.includes(`href="${href}"`));
        assert.ok(markup.startsWith('<p class="'));
        assert.ok(markup.endsWith('</a>).</p>'));
    });
}

for (const text of [
    'hello@example.com',
    'hello@www.example.com',
    '@laravel',
    '@inertiajs/react',
    'javascript:alert(1)',
    'data:text/html,<script>alert(1)</script>',
    'file:///etc/passwd',
    'http://',
    'https://',
    'app.name',
    'index.php',
]) {
    test(`leaves non-URL bio text unlinked: ${text}`, () => {
        const markup = renderToStaticMarkup(<ProfileBio bio={text} />);

        assert.ok(!markup.includes('<a'));
    });
}
