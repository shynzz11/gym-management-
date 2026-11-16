<?php

use App\Support\HtmlSanitizer;

test('keeps the markup the legal rich text editor produces', function () {
    $html = '<h2>Privacy</h2><p style="text-align: center">We <strong>never</strong> share <em>your</em> <u>data</u>.</p>'
        .'<ul><li>One</li><li>Two</li></ul><blockquote><p>Quote</p></blockquote><hr>'
        .'<p><a href="https://example.com/terms" target="_blank">Terms</a> and <a href="/contact-us">contact</a></p>';

    expect(HtmlSanitizer::clean($html))->toBe(
        '<h2>Privacy</h2><p style="text-align: center">We <strong>never</strong> share <em>your</em> <u>data</u>.</p>'
        .'<ul><li>One</li><li>Two</li></ul><blockquote><p>Quote</p></blockquote><hr>'
        .'<p><a href="https://example.com/terms" target="_blank" rel="noopener noreferrer nofollow">Terms</a>'
        .' and <a href="/contact-us" rel="noopener noreferrer nofollow">contact</a></p>'
    );
});

test('removes scripts, event handlers and dangerous elements', function (string $payload) {
    $clean = HtmlSanitizer::clean($payload);

    expect(strtolower($clean))
        ->not->toContain('<script')
        ->not->toContain('onerror')
        ->not->toContain('onload')
        ->not->toContain('onclick')
        ->not->toContain('<iframe')
        ->not->toContain('<svg')
        ->not->toContain('<img')
        ->not->toContain('<style')
        ->not->toContain('javascript:');
})->with([
    'script tag' => '<p>Hi</p><script>alert(1)</script>',
    'img onerror' => '<img src=x onerror=alert(1)>',
    'svg onload' => '<svg onload=alert(1)><circle /></svg>',
    'iframe' => '<iframe src="https://evil.test"></iframe>',
    'inline handler on allowed tag' => '<p onclick="alert(1)">Click</p>',
    'style block' => '<style>body{display:none}</style><p>x</p>',
    'javascript href' => '<a href="javascript:alert(1)">x</a>',
    'obfuscated javascript href' => '<a href=" java&#x09;script:alert(1)">x</a>',
    'entity encoded href' => '<a href="&#106;avascript:alert(1)">x</a>',
    'breaks out of wrapper' => '</div><script>alert(1)</script><img src=x onerror=alert(1)>',
    'uppercase tags' => '<SCRIPT>alert(1)</SCRIPT><IMG SRC=x ONERROR=alert(1)>',
]);

test('drops non-alignment styles and unsafe link schemes but keeps the text', function () {
    expect(HtmlSanitizer::clean('<p style="background:url(javascript:alert(1))">Text</p>'))->toBe('<p>Text</p>')
        ->and(HtmlSanitizer::clean('<a href="data:text/html,<script>alert(1)</script>">Link</a>'))
        ->toBe('<a rel="noopener noreferrer nofollow">Link</a>');
});

test('unwraps unknown elements and escapes text', function () {
    expect(HtmlSanitizer::clean('<div><span>a &lt;b&gt; &amp; "c"</span></div>'))
        ->toBe('a &lt;b&gt; &amp; &quot;c&quot;');
});

test('preserves multibyte text and passes empty values through', function () {
    expect(HtmlSanitizer::clean('<p>سياسة الخصوصية — ₹499</p>'))->toBe('<p>سياسة الخصوصية — ₹499</p>')
        ->and(HtmlSanitizer::clean(null))->toBeNull()
        ->and(HtmlSanitizer::clean(''))->toBe('');
});
