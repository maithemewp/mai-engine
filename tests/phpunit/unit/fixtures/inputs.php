<?php
/**
 * Inputs for the DOM encoding characterization fixture.
 *
 * Goldens live in encoding.php and are GENERATED from these by generate.php. Never
 * hand-edit a golden. Adding a row here and regenerating is the only supported change.
 *
 * Groups:
 *   g1  baseline content classes
 *   g2  Unicode noncharacters and the numeric boundary
 *   g3  escaping-sensitive input. PINS DANGEROUS BEHAVIOR, see F3 in the spec
 *   g4  malformed and structural
 *   g5  international content beyond g1
 *   g6  inline SVG (lib/functions/icons.php is a caller)
 *   g7  quotes, and JSON in data attributes. PINS F5
 */

return [
	// G1: baseline content classes.
	'g1_polish_diacritics'   => '<p>Zażółć gęślą jaźń</p>',
	'g1_curly_quotes'        => '<p>“quoted” and ‘single’ and it’s</p>',
	'g1_dashes'              => '<p>a — b – c</p>',
	'g1_entity_amp'          => '<p>&amp;</p>',
	'g1_entity_lt_gt'        => '<p>&lt;tag&gt;</p>',
	'g1_entity_quot'         => '<p>&quot;q&quot;</p>',
	'g1_entity_apos'         => '<p>&#039;a&#039;</p>',
	'g1_entity_nbsp'         => '<p>a&nbsp;b</p>',
	'g1_double_escaped'      => '<p>&amp;amp;</p>',
	'g1_bare_ampersand'      => '<p>Tom & Jerry</p>',
	'g1_amp_in_href'         => '<a href="?a=1&amp;b=2">link</a>',
	'g1_emoji_bmp'           => '<p>☕</p>',
	'g1_emoji_astral'        => '<p>🎉</p>',
	'g1_emoji_zwj'           => '<p>👩‍💻</p>',
	'g1_numeric_entity'      => '<p>&#380;</p>',
	'g1_nested_markup'       => '<p><strong>bold</strong> and <em>italic</em></p>',
	'g1_combined'            => '<p>Zażółć &amp; “curly” — 🎉&nbsp;end</p>',

	// G2: Unicode noncharacters and the numeric boundary. This is the entire divergence
	// surface between the current implementation and candidate A. Do not drop this group.
	'g2_null'                => '<p>&#0;</p>',
	'g2_surrogate'           => '<p>&#55296;</p>',
	'g2_fdd0'                => '<p>&#64976;</p>',
	'g2_replacement'         => '<p>&#65533;</p>',
	'g2_fffe'                => '<p>&#65534;</p>',
	'g2_ffff'                => '<p>&#65535;</p>',
	'g2_10fffd'              => '<p>&#1114109;</p>',
	'g2_10fffe'              => '<p>&#1114110;</p>',
	'g2_10ffff'              => '<p>&#1114111;</p>',
	'g2_out_of_range'        => '<p>&#1114112;</p>',

	// G3: escaping-sensitive. PINS DANGEROUS BEHAVIOR. See F3 in the spec.
	'g3_escaped_script'      => '<p>&lt;script&gt;alert(1)&lt;/script&gt;</p>',
	'g3_escaped_img_onerror' => '<p>&lt;img src=x onerror=alert(1)&gt;</p>',
	'g3_quot_in_attribute'   => '<a title="a&quot; onmouseover=&quot;alert(1)">x</a>',
	'g3_apos_in_attribute'   => '<a title="a&#039;b">x</a>',
	'g3_lt_in_attribute'     => '<a title="a&lt;b">x</a>',
	'g3_script_body'         => '<script>if (a &amp;&amp; b &lt; c) x("&quot;");</script>',
	'g3_style_body'          => '<style>a[title="&quot;"] { color: red; }</style>',
	'g3_textarea'            => '<textarea>&lt;b&gt; &amp; é</textarea>',
	'g3_pre'                 => '<pre>&lt;tag&gt; &amp;amp;</pre>',

	// G4: malformed and structural.
	'g4_unclosed_tag'        => '<p>unclosed',
	'g4_crossed_tags'        => '<p><b>x</p></b>',
	'g4_stray_lt'            => '<p>a < b</p>',
	'g4_stray_gt'            => '<p>a > b</p>',
	'g4_comment_with_entity' => '<p>x</p><!-- &amp; comment -->',
	'g4_cdata'               => '<p><![CDATA[a & b]]></p>',
	'g4_empty'               => '',
	'g4_text_only'           => 'plain text',

	// G5: international content beyond G1.
	'g5_rtl_arabic'          => '<p>مرحبا بالعالم</p>',
	'g5_rtl_hebrew'          => '<p>שלום עולם</p>',
	'g5_bidi_controls'       => "<p>a\u{200E}b\u{200F}c\u{202B}d\u{202C}e</p>",
	'g5_cjk'                 => '<p>日本語のテキスト</p>',
	'g5_nfc_precomposed'     => "<p>\u{00E9}</p>",
	'g5_nfd_combining'       => "<p>e\u{0301}</p>",
	'g5_vietnamese'          => '<p>Tiếng Việt nghiêng</p>',
	'g5_raw_nbsp'            => "<p>a\u{00A0}b</p>",
	'g5_soft_hyphen'         => "<p>sig\u{00AD}nal</p>",

	// G6: inline SVG. lib/functions/icons.php is a caller, and HTML named entities are not
	// valid in XML or SVG.
	'g6_inline_svg'          => '<svg viewBox="0 0 10 10"><title>A &amp; B</title><path d="M0 0"/></svg>',
	'g6_svg_with_accent'     => '<svg><title>Zażółć é</title></svg>',

	// G7: quotes, and JSON in data attributes. PINS F5, a live HTML corruption bug: saveHTML()
	// escapes these correctly and the decode step then breaks them out of their own quotes.
	// Every g7_data_* row currently records corrupted output.
	'g7_straight_and_curly'  => '<p>She said "hi" and \'bye\' then “hi” and ‘bye’ and it’s fine</p>',
	'g7_polish_both_cases'   => '<p>ZAŻÓŁĆ GĘŚLĄ JAŹŃ / zażółć gęślą jaźń / ĄĆĘŁŃÓŚŹŻ ąćęłńóśźż</p>',
	'g7_typographic'         => '<p>© ® ™ ° ½ € £ ¥ § ¶ † ‡ • … ‰ ± × ÷ ≠ ≤ ≥ → ← ↔</p>',
	'g7_data_json_single'    => '<div data-config=\'{"title":"Zażółć","q":"say \"hi\"","n":1,"ok":true}\'>x</div>',
	'g7_data_json_escaped'   => '<div data-config="{&quot;title&quot;:&quot;A &amp; B&quot;,&quot;n&quot;:1}">x</div>',
	'g7_data_mixed_attrs'    => '<button data-a="1" data-label="Zażółć “x”" data-json=\'{"k":"v & w"}\' aria-label="It’s">go</button>',
	'g7_data_url_and_json'   => '<a href="/x?a=1&amp;b=2&amp;c=%20" data-track=\'{"u":"/x?a=1&b=2"}\'>l</a>',
	'g7_kitchen_sink'        => '<div class="c" data-cfg=\'{"t":"Zażółć & “curly”","d":"—"}\'><p>He said "it’s" — 🎉 &amp; &nbsp;done</p></div>',
];
