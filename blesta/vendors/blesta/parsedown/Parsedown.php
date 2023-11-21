<?php

#
#
# Parsedown
# http://parsedown.org
#
# (c) Emanuil Rusev
# http://erusev.com
#
# For the full license information, view the LICENSE file that was distributed
# with this source code.
#
#

class Parsedown
{

    private $safeMode = false;

    #
    # Philosophy

    # Markdown is intended to be easy-to-read by humans - those of us who read
    # line by line, left to right, top to bottom. In order to take advantage of
    # this, Parsedown tries to read in a similar way. It breaks texts into
    # lines, it iterates through them and it looks at how they start and relate
    # to each other.

    #
    # ~

    function text($text)
    {
        if ($this->safeMode) {
            $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        }

        # standardize line breaks
        $text = str_replace("\r\n", "\n", $text);
        $text = str_replace("\r", "\n", $text);

        # replace tabs with spaces
        $text = str_replace("\t", '    ', $text);

        # remove surrounding line breaks
        $text = trim($text, "\n");

        # split text into lines
        $lines = explode("\n", $text);

        # iterate through lines to identify blocks
        $markup = $this->lines($lines);

        # trim line breaks
        $markup = trim($markup, "\n");

        # clean up
        $this->Text = [];

        return $markup;
    }

    #
    # Setters
    #

    private $breaksEnabled;

    function setBreaksEnabled($breaksEnabled)
    {
        $this->breaksEnabled = $breaksEnabled;

        return $this;
    }

    function setSafeMode($safeMode)
    {
        $this->safeMode = $safeMode;

        return $this;
    }

    #
    # Lines
    #

    protected $Block = [
        '#' => ['Atx'],
        '*' => ['Rule', 'List'],
        '+' => ['List'],
        '-' => ['Setext', 'Table', 'Rule', 'List'],
        '0' => ['List'],
        '1' => ['List'],
        '2' => ['List'],
        '3' => ['List'],
        '4' => ['List'],
        '5' => ['List'],
        '6' => ['List'],
        '7' => ['List'],
        '8' => ['List'],
        '9' => ['List'],
        ':' => ['Table'],
        '<' => ['Markup'],
        '=' => ['Setext'],
        '>' => ['Quote'],
        '_' => ['Rule'],
        '`' => ['FencedCode'],
        '|' => ['Table'],
        '~' => ['FencedCode'],
    ];

    # ~

    protected $Definition = [
        '[' => ['Reference'],
    ];

    # ~

    protected $unmarkedBlockTypes = [
        'CodeBlock',
    ];

    #
    # Blocks
    #

    private function lines(array $lines)
    {
        $CurrentBlock = ['type' => '', 'element' => ''];

        foreach ($lines as $line) {
            if (chop($line) === '') {
                if (isset($CurrentBlock)) {
                    $CurrentBlock['interrupted'] = true;
                }

                continue;
            }

            # If line begins with '>' htmlentity, convert to '>' for quoting
            if ($this->safeMode and substr(trim($line), 0, 4) == "&gt;") {
                $strpos = strpos($line, "&gt;");
                if ($strpos === 0 || (!isset($CurrentBlock['interrupted']) || !$CurrentBlock['interrupted'])) {
                    $line = substr_replace($line, ">", $strpos, 4);
                }
            }

            $indent = 0;

            while (isset($line[$indent]) and $line[$indent] === ' ') {
                $indent++;
            }

            $text = $indent > 0 ? substr($line, $indent) : $line;

            # ~

            $Line = ['body' => $line, 'indent' => $indent, 'text' => $text];

            # ~

            if (isset($CurrentBlock['incomplete'])) {
                $Block = $this->{'addTo' . $CurrentBlock['type']}($Line, $CurrentBlock);

                if (isset($Block)) {
                    $CurrentBlock = $Block;

                    continue;
                } else {
                    if (method_exists($this, 'complete' . $CurrentBlock['type'])) {
                        $CurrentBlock = $this->{'complete' . $CurrentBlock['type']}($CurrentBlock);
                    }

                    unset($CurrentBlock['incomplete']);
                }
            }

            # ~

            $marker = $text[0];

            if (isset($this->Definition[$marker])) {
                foreach ($this->Definition[$marker] as $definitionType) {
                    $Definition = $this->{'identify' . $definitionType}($Line, $CurrentBlock);

                    if (isset($Definition)) {
                        $this->Text[$definitionType][$Definition['id']] = $Definition['data'];

                        continue 2;
                    }
                }
            }

            # ~

            $blockTypes = $this->unmarkedBlockTypes;

            if (isset($this->Block[$marker])) {
                foreach ($this->Block[$marker] as $blockType) {
                    $blockTypes [] = $blockType;
                }
            }

            #
            # ~

            foreach ($blockTypes as $blockType) {
                $Block = $this->{'identify' . $blockType}($Line, $CurrentBlock);

                if (isset($Block)) {
                    $Block['type'] = $blockType;

                    if (!isset($Block['identified'])) {
                        $Elements [] = $CurrentBlock['element'];

                        $Block['identified'] = true;
                    }

                    if (method_exists($this, 'addTo' . $blockType)) {
                        $Block['incomplete'] = true;
                    }

                    $CurrentBlock = $Block;

                    continue 2;
                }
            }

            # ~

            if ($CurrentBlock['type'] === 'Paragraph' and !isset($CurrentBlock['interrupted'])) {
                $CurrentBlock['element']['text'] .= "\n" . $text;
            } else {
                $Elements [] = $CurrentBlock['element'];

                $CurrentBlock = [
                    'type' => 'Paragraph',
                    'identified' => true,
                    'element' => [
                        'name' => 'p',
                        'text' => $text,
                        'handler' => 'line',
                    ],
                ];
            }
        }

        # ~

        if (isset($CurrentBlock['incomplete']) and method_exists($this, 'complete' . $CurrentBlock['type'])) {
            $CurrentBlock = $this->{'complete' . $CurrentBlock['type']}($CurrentBlock);
        }

        # ~

        $Elements [] = $CurrentBlock['element'];

        unset($Elements[0]);

        # ~

        return $this->elements($Elements);
    }

    #
    # Atx

    protected function identifyAtx($Line)
    {
        if (isset($Line['text'][1])) {
            $level = 1;

            while (isset($Line['text'][$level]) and $Line['text'][$level] === '#') {
                $level++;
            }

            $text = trim($Line['text'], '# ');

            $Block = [
                'element' => [
                    'name' => 'h' . $level,
                    'text' => $text,
                    'handler' => 'line',
                ],
            ];

            return $Block;
        }
    }

    #
    # Code

    protected function identifyCodeBlock($Line)
    {
        if ($Line['indent'] >= 4) {
            $text = substr($Line['body'], 4);

            $Block = [
                'element' => [
                    'name' => 'pre',
                    'handler' => 'element',
                    'text' => [
                        'name' => 'code',
                        'text' => $text,
                    ],
                ],
            ];

            return $Block;
        }
    }

    protected function addToCodeBlock($Line, $Block)
    {
        if ($Line['indent'] >= 4) {
            if (isset($Block['interrupted'])) {
                $Block['element']['text']['text'] .= "\n";

                unset($Block['interrupted']);
            }

            $Block['element']['text']['text'] .= "\n";

            $text = substr($Line['body'], 4);

            $Block['element']['text']['text'] .= $text;

            return $Block;
        }
    }

    protected function completeCodeBlock($Block)
    {
        $text = $Block['element']['text']['text'];

        if (!$this->safeMode) {
            $text = htmlspecialchars($text, ENT_NOQUOTES, 'UTF-8');
        }

        $Block['element']['text']['text'] = $text;

        return $Block;
    }

    #
    # Fenced Code

    protected function identifyFencedCode($Line)
    {
        if (preg_match('/^([' . $Line['text'][0] . ']{3,})[ ]*([\w-]+)?[ ]*$/', $Line['text'], $matches)) {
            $Element = [
                'name' => 'code',
                'text' => '',
            ];

            if (isset($matches[2])) {
                $class = 'language-' . $matches[2];

                $Element['attributes'] = [
                    'class' => $class,
                ];
            }

            $Block = [
                'char' => $Line['text'][0],
                'element' => [
                    'name' => 'pre',
                    'handler' => 'element',
                    'text' => $Element,
                ],
            ];

            return $Block;
        }
    }

    protected function addToFencedCode($Line, $Block)
    {
        if (isset($Block['complete'])) {
            return;
        }

        if (isset($Block['interrupted'])) {
            $Block['element']['text']['text'] .= "\n";

            unset($Block['interrupted']);
        }

        if (preg_match('/^' . $Block['char'] . '{3,}[ ]*$/', $Line['text'])) {
            $Block['element']['text']['text'] = substr($Block['element']['text']['text'], 1);

            $Block['complete'] = true;

            return $Block;
        }

        $Block['element']['text']['text'] .= "\n" . $Line['body'];

        return $Block;
    }

    protected function completeFencedCode($Block)
    {
        $text = $Block['element']['text']['text'];

        if (!$this->safeMode) {
            $text = htmlspecialchars($text, ENT_NOQUOTES, 'UTF-8');
        }

        $Block['element']['text']['text'] = $text;

        return $Block;
    }

    #
    # List

    protected function identifyList($Line)
    {
        [$name, $pattern] = $Line['text'][0] <= '-' ? ['ul', '[*+-]'] : ['ol', '[0-9]+[.]'];

        if (preg_match('/^(' . $pattern . '[ ]+)(.*)/', $Line['text'], $matches)) {
            $Block = [
                'indent' => $Line['indent'],
                'pattern' => $pattern,
                'element' => [
                    'name' => $name,
                    'handler' => 'elements',
                ],
            ];

            $Block['li'] = [
                'name' => 'li',
                'handler' => 'li',
                'text' => [
                    $matches[2],
                ],
            ];

            $Block['element']['text'] [] = &$Block['li'];

            return $Block;
        }
    }

    protected function addToList($Line, array $Block)
    {
        if ($Block['indent'] === $Line['indent'] and preg_match('/^' . $Block['pattern'] . '[ ]+(.*)/', $Line['text'], $matches)) {
            if (isset($Block['interrupted'])) {
                $Block['li']['text'] [] = '';

                unset($Block['interrupted']);
            }

            unset($Block['li']);

            $Block['li'] = [
                'name' => 'li',
                'handler' => 'li',
                'text' => [
                    $matches[1],
                ],
            ];

            $Block['element']['text'] [] = &$Block['li'];

            return $Block;
        }

        if (!isset($Block['interrupted'])) {
            $text = preg_replace('/^[ ]{0,4}/', '', $Line['body']);

            $Block['li']['text'] [] = $text;

            return $Block;
        }

        if ($Line['indent'] > 0) {
            $Block['li']['text'] [] = '';

            $text = preg_replace('/^[ ]{0,4}/', '', $Line['body']);

            $Block['li']['text'] [] = $text;

            unset($Block['interrupted']);

            return $Block;
        }
    }

    #
    # Quote

    protected function identifyQuote($Line)
    {
        if (preg_match('/^>[ ]?(.*)/', $Line['text'], $matches)) {
            $Block = [
                'element' => [
                    'name' => 'blockquote',
                    'handler' => 'lines',
                    'text' => (array) $matches[1],
                ],
            ];

            return $Block;
        }
    }

    protected function addToQuote($Line, array $Block)
    {
        if ($Line['text'][0] === '>' and preg_match('/^>[ ]?(.*)/', $Line['text'], $matches)) {
            if (isset($Block['interrupted'])) {
                $Block['element']['text'] [] = '';

                unset($Block['interrupted']);
            }

            $Block['element']['text'] [] = $matches[1];

            return $Block;
        }

        if (!isset($Block['interrupted'])) {
            $Block['element']['text'] [] = $Line['text'];

            return $Block;
        }
    }

    #
    # Rule

    protected function identifyRule($Line)
    {
        if (preg_match('/^([' . $Line['text'][0] . '])([ ]{0,2}\1){2,}[ ]*$/', $Line['text'])) {
            $Block = [
                'element' => [
                    'name' => 'hr'
                ],
            ];

            return $Block;
        }
    }

    #
    # Setext

    protected function identifySetext($Line, array $Block = null)
    {
        if (!isset($Block) or $Block['type'] !== 'Paragraph' or isset($Block['interrupted'])) {
            return;
        }

        if (chop($Line['text'], $Line['text'][0]) === '') {
            $Block['element']['name'] = $Line['text'][0] === '=' ? 'h1' : 'h2';

            return $Block;
        }
    }

    #
    # Markup

    protected function identifyMarkup($Line)
    {
        if (preg_match('/^<(\w[\w\d]*)(?:[ ][^>\/]*)?(\/?)[ ]*>/', $Line['text'], $matches)) {
            if (in_array($matches[1], $this->textLevelElements)) {
                return;
            }

            $Block = [
                'element' => $Line['body'],
            ];

            if ($matches[2] or $matches[1] === 'hr' or preg_match('/<\/' . $matches[1] . '>[ ]*$/', $Line['text'])) {
                $Block['closed'] = true;
            } else {
                $Block['depth'] = 0;
                $Block['start'] = '<' . $matches[1] . '>';
                $Block['end'] = '</' . $matches[1] . '>';
            }

            return $Block;
        }
    }

    protected function addToMarkup($Line, array $Block)
    {
        if (isset($Block['closed'])) {
            return;
        }

        if (stripos($Line['text'], $Block['start']) !== false) # opening tag
        {
            $Block['depth']++;
        }

        if (stripos($Line['text'], $Block['end']) !== false) # closing tag
        {
            if ($Block['depth'] > 0) {
                $Block['depth']--;
            } else {
                $Block['closed'] = true;
            }
        }

        $Block['element'] .= "\n" . $Line['body'];

        return $Block;
    }

    #
    # Table

    protected function identifyTable($Line, array $Block = null)
    {
        if (!isset($Block) or $Block['type'] !== 'Paragraph' or isset($Block['interrupted'])) {
            return;
        }

        if (strpos($Block['element']['text'], '|') !== false and chop($Line['text'], ' -:|') === '') {
            $alignments = [];

            $divider = $Line['text'];

            $divider = trim($divider);
            $divider = trim($divider, '|');

            $dividerCells = explode('|', $divider);

            foreach ($dividerCells as $dividerCell) {
                $dividerCell = trim($dividerCell);

                if ($dividerCell === '') {
                    continue;
                }

                $alignment = null;

                if ($dividerCell[0] === ':') {
                    $alignment = 'left';
                }

                if (substr($dividerCell, -1) === ':') {
                    $alignment = $alignment === 'left' ? 'center' : 'right';
                }

                $alignments [] = $alignment;
            }

            # ~

            $HeaderElements = [];

            $header = $Block['element']['text'];

            $header = trim($header);
            $header = trim($header, '|');

            $headerCells = explode('|', $header);

            foreach ($headerCells as $index => $headerCell) {
                $headerCell = trim($headerCell);

                $HeaderElement = [
                    'name' => 'th',
                    'text' => $headerCell,
                    'handler' => 'line',
                ];

                if (isset($alignments[$index])) {
                    $alignment = $alignments[$index];

                    $HeaderElement['attributes'] = [
                        'align' => $alignment,
                    ];
                }

                $HeaderElements [] = $HeaderElement;
            }

            # ~

            $Block = [
                'alignments' => $alignments,
                'identified' => true,
                'element' => [
                    'name' => 'table',
                    'handler' => 'elements',
                ],
            ];

            $Block['element']['text'] [] = [
                'name' => 'thead',
                'handler' => 'elements',
            ];

            $Block['element']['text'] [] = [
                'name' => 'tbody',
                'handler' => 'elements',
                'text' => [],
            ];

            $Block['element']['text'][0]['text'] [] = [
                'name' => 'tr',
                'handler' => 'elements',
                'text' => $HeaderElements,
            ];

            return $Block;
        }
    }

    protected function addToTable($Line, array $Block)
    {
        if ($Line['text'][0] === '|' or strpos($Line['text'], '|')) {
            $Elements = [];

            $row = $Line['text'];

            $row = trim($row);
            $row = trim($row, '|');

            $cells = explode('|', $row);

            foreach ($cells as $index => $cell) {
                $cell = trim($cell);

                $Element = [
                    'name' => 'td',
                    'handler' => 'line',
                    'text' => $cell,
                ];

                if (isset($Block['alignments'][$index])) {
                    $Element['attributes'] = [
                        'align' => $Block['alignments'][$index],
                    ];
                }

                $Elements [] = $Element;
            }

            $Element = [
                'name' => 'tr',
                'handler' => 'elements',
                'text' => $Elements,
            ];

            $Block['element']['text'][1]['text'] [] = $Element;

            return $Block;
        }
    }

    #
    # Definitions
    #

    protected function identifyReference($Line)
    {
        if (preg_match('/^\[(.+?)\]:[ ]*<?(\S+?)>?(?:[ ]+(["\'(]?)(.+?)(["\')]?))?[ ]*$/', $Line['text'], $matches)) {
            if (isset($matches[3]) and trim($matches[3]) == "") {
                if (!$this->safeMode or !isset($matches[4]) or !in_array(substr($matches[4], 0, 6), ["&quot;", "&#039;"])) {
                    return;
                }
            }
            $url = $matches[2];
            if ($this->safeMode && stripos($url, "javascript:") !== false) {
                $url = "";
            }

            $Definition = [
                'id' => strtolower($matches[1]),
                'data' => [
                    'url' => $url,
                ],
            ];

            if (!$this->safeMode and isset($matches[4])) {
                $Definition['data']['title'] = $matches[4];
            }

            return $Definition;
        }
    }

    #
    # ~
    #

    private function element(array $Element)
    {
        $markup = '<' . $Element['name'];

        if (isset($Element['attributes'])) {
            foreach ($Element['attributes'] as $name => $value) {
                $markup .= ' ' . $name . '="' . $value . '"';
            }
        }

        if (isset($Element['text'])) {
            $markup .= '>';

            if (isset($Element['handler'])) {
                $markup .= $this->{$Element['handler']}($Element['text']);
            } else {
                $markup .= $Element['text'];
            }

            $markup .= '</' . $Element['name'] . '>';
        } else {
            $markup .= ' />';
        }

        return $markup;
    }

    private function elements(array $Elements)
    {
        $markup = '';

        foreach ($Elements as $Element) {
            if ($Element === null) {
                continue;
            }

            $markup .= "\n";

            if (is_string($Element)) # because of Markup
            {
                $markup .= $Element;

                continue;
            }

            $markup .= $this->element($Element);
        }

        $markup .= "\n";

        return $markup;
    }

    #
    # Spans
    #

    protected $Span = [
        '!' => ['Link'], # ?
        '&' => ['Ampersand'],
        '*' => ['Emphasis'],
        '/' => ['Url'],
        '<' => ['UrlTag', 'EmailTag', 'Tag', 'LessThan'],
        '[' => ['Link'],
        '_' => ['Emphasis'],
        '`' => ['InlineCode'],
        '~' => ['Strikethrough'],
        '\\' => ['EscapeSequence'],
    ];

    # ~

    protected $spanMarkerList = '*_!&[</`~\\';

    #
    # ~
    #

    public function line($text)
    {
        $markup = '';

        $remainder = $text;

        $markerPosition = 0;

        while ($markedExcerpt = strpbrk($remainder, $this->spanMarkerList)) {
            $marker = $markedExcerpt[0];

            $markerPosition += strpos($remainder, $marker);

            foreach ($this->Span[$marker] as $spanType) {
                $handler = 'identify' . $spanType;

                $Span = $this->$handler($markedExcerpt, $text);

                if (!isset($Span)) {
                    continue;
                }

                # The identified span can be ahead of the marker.

                if (isset($Span['position']) and $Span['position'] > $markerPosition) {
                    continue;
                }

                # Spans that start at the position of their marker don't have to set a position.

                if (!isset($Span['position'])) {
                    $Span['position'] = $markerPosition;
                }

                $plainText = substr($text, 0, $Span['position']);

                $markup .= $this->readPlainText($plainText);

                $markup .= isset($Span['element']) ? $this->element($Span['element']) : $Span['markup'];

                $text = substr($text, $Span['position'] + $Span['extent']);

                $remainder = $text;

                $markerPosition = 0;

                continue 2;
            }

            $remainder = substr($markedExcerpt, 1);

            $markerPosition++;
        }

        $markup .= $this->readPlainText($text);

        return $markup;
    }

    #
    # ~
    #

    protected function identifyUrl($excerpt, $text)
    {
        if (!isset($excerpt[1]) or $excerpt[1] !== '/') {
            return;
        }

        if (preg_match('/\bhttps?:[\/]{2}[^\s<]+\b\/*/ui', $text, $matches, PREG_OFFSET_CAPTURE)) {
            $url = $matches[0][0];
            if (!$this->safeMode) {
                $url = str_replace(['&', '<', '>'], ['&amp;', '&lt;', '&gt;'], $url);
            } else if (stripos($url, "javascript:") !== false) {
                $url = "";
            }

            return [
                'extent' => strlen($matches[0][0]),
                'position' => $matches[0][1],
                'element' => [
                    'name' => 'a',
                    'text' => $url,
                    'attributes' => [
                        'href' => $url,
                    ],
                ],
            ];
        }
    }

    protected function identifyAmpersand($excerpt)
    {
        if (!preg_match('/^&#?\w+;/', $excerpt)) {
            return [
                'markup' => '&amp;',
                'extent' => 1,
            ];
        }
    }

    protected function identifyStrikethrough($excerpt)
    {
        if (!isset($excerpt[1])) {
            return;
        }

        if ($excerpt[1] === $excerpt[0] and preg_match('/^~~(?=\S)(.+?)(?<=\S)~~/', $excerpt, $matches)) {
            return [
                'extent' => strlen($matches[0]),
                'element' => [
                    'name' => 'del',
                    'text' => $matches[1],
                    'handler' => 'line',
                ],
            ];
        }
    }

    protected function identifyEscapeSequence($excerpt)
    {
        if (isset($excerpt[1]) && in_array($excerpt[1], $this->specialCharacters)) {
            return [
                'markup' => $excerpt[1],
                'extent' => 2,
            ];
        }
    }

    protected function identifyLessThan()
    {
        return [
            'markup' => '&lt;',
            'extent' => 1,
        ];
    }

    protected function identifyUrlTag($excerpt)
    {
        if (strpos($excerpt, '>') !== false and preg_match('/^<(https?:[\/]{2}[^\s]+?)>/i', $excerpt, $matches)) {
            $url = $matches[1];
            if (!$this->safeMode) {
                $url = str_replace(['&', '<', '>'], ['&amp;', '&lt;', '&gt;'], $url);
            } else if (stripos($url, "javascript:") !== false) {
                $url = "";
            }

            return [
                'extent' => strlen($matches[0]),
                'element' => [
                    'name' => 'a',
                    'text' => $url,
                    'attributes' => [
                        'href' => $url,
                    ],
                ],
            ];
        }
    }

    protected function identifyEmailTag($excerpt)
    {
        if (strpos($excerpt, '>') !== false and preg_match('/^<(\S+?@\S+?)>/', $excerpt, $matches)) {
            return [
                'extent' => strlen($matches[0]),
                'element' => [
                    'name' => 'a',
                    'text' => $matches[1],
                    'attributes' => [
                        'href' => 'mailto:' . $matches[1],
                    ],
                ],
            ];
        }
    }

    protected function identifyTag($excerpt)
    {
        if (strpos($excerpt, '>') !== false and preg_match('/^<\/?\w.*?>/', $excerpt, $matches)) {
            return [
                'markup' => $matches[0],
                'extent' => strlen($matches[0]),
            ];
        }
    }

    protected function identifyInlineCode($excerpt)
    {
        $marker = $excerpt[0];

        if (preg_match('/^(' . $marker . '+)[ ]*(.+?)[ ]*(?<!' . $marker . ')\1(?!' . $marker . ')/', $excerpt, $matches)) {
            $text = $matches[2];
            if (!$this->safeMode) {
                $text = htmlspecialchars($text, ENT_NOQUOTES, 'UTF-8');
            }

            return [
                'extent' => strlen($matches[0]),
                'element' => [
                    'name' => 'code',
                    'text' => $text,
                ],
            ];
        }
    }

    protected function identifyLink($excerpt)
    {
        $extent = $excerpt[0] === '!' ? 1 : 0;

        if (strpos($excerpt, ']') and preg_match('/\[((?:[^][]|(?R))*)\]/', $excerpt, $matches)) {
            $Link = ['text' => $matches[1], 'label' => strtolower($matches[1])];

            $extent += strlen($matches[0]);

            $substring = substr($excerpt, $extent);

            if (preg_match('/^\s*\[(.+?)\]/', $substring, $matches)) {
                $Link['label'] = strtolower($matches[1]);

                if (isset($this->Text['Reference'][$Link['label']])) {
                    $Link += $this->Text['Reference'][$Link['label']];

                    $extent += strlen($matches[0]);
                } else {
                    return;
                }
            } else if (isset($this->Text['Reference'][$Link['label']])) {
                $Link += $this->Text['Reference'][$Link['label']];

                if (preg_match('/^[ ]*\[\]/', $substring, $matches)) {
                    $extent += strlen($matches[0]);
                }
            } else if (preg_match('/^\([ ]*(.*?)(?:[ ]+[\'"]?(.+?)[\'"]?)?[ ]*\)/', $substring, $matches)) {
                $Link['url'] = $matches[1];

                if (isset($matches[2])) {
                    $Link['title'] = $matches[2];
                }

                $extent += strlen($matches[0]);
            } else {
                return;
            }
        } else {
            return;
        }

        $url = $Link['url'];
        if (!$this->safeMode) {
            $url = str_replace(['&', '<', '>'], ['&amp;', '&lt;', '&gt;'], $url);
        } else if (stripos($url, "javascript:") !== false) {
            $url = "";
        }

        if ($excerpt[0] === '!') {
            $Element = [
                'name' => 'img',
                'attributes' => [
                    'alt' => $Link['text'],
                    'src' => $url,
                ],
            ];
        } else {
            $Element = [
                'name' => 'a',
                'handler' => 'line',
                'text' => $Link['text'],
                'attributes' => [
                    'href' => $url,
                ],
            ];
        }

        if (!$this->safeMode && isset($Link['title'])) {
            $Element['attributes']['title'] = $Link['title'];
        }

        return [
            'extent' => $extent,
            'element' => $Element,
        ];
    }

    protected function identifyEmphasis($excerpt)
    {
        if (!isset($excerpt[1])) {
            return;
        }

        $marker = $excerpt[0];

        if ($excerpt[1] === $marker and preg_match($this->strongRegex[$marker], $excerpt, $matches)) {
            $emphasis = 'strong';
        } else if (preg_match($this->emRegex[$marker], $excerpt, $matches)) {
            $emphasis = 'em';
        } else {
            return;
        }

        return [
            'extent' => strlen($matches[0]),
            'element' => [
                'name' => $emphasis,
                'handler' => 'line',
                'text' => $matches[1],
            ],
        ];
    }

    #
    # ~

    protected function readPlainText($text)
    {
        $breakMarker = $this->breaksEnabled ? "\n" : "  \n";

        return str_replace($breakMarker, "<br />\n", $text);
    }

    #
    # ~
    #

    protected function li($lines)
    {
        $markup = $this->lines($lines);

        $trimmedMarkup = trim($markup);

        if (!in_array('', $lines) and substr($trimmedMarkup, 0, 3) === '<p>') {
            $markup = $trimmedMarkup;
            $markup = substr($markup, 3);

            $position = strpos($markup, "</p>");

            $markup = substr_replace($markup, '', $position, 4);
        }

        return $markup;
    }

    #
    # Multiton
    #

    static function instance($name = 'default')
    {
        if (isset(self::$instances[$name])) {
            return self::$instances[$name];
        }

        $instance = new self();

        self::$instances[$name] = $instance;

        return $instance;
    }

    private static $instances = [];

    #
    # Deprecated Methods
    #

    /**
     * @deprecated in favor of "text"
     */
    function parse($text)
    {
        return $this->text($text);
    }

    #
    # Fields
    #

    protected $Text;

    #
    # Read-only

    protected $specialCharacters = [
        '\\',
        '`',
        '*',
        '_',
        '{',
        '}',
        '[',
        ']',
        '(',
        ')',
        '>',
        '#',
        '+',
        '-',
        '.',
        '!',
    ];

    protected $strongRegex = [
        '*' => '/^[*]{2}((?:[^*]|[*][^*]*[*])+?)[*]{2}(?![*])/s',
        '_' => '/^__((?:[^_]|_[^_]*_)+?)__(?!_)/us',
    ];

    protected $emRegex = [
        '*' => '/^[*]((?:[^*]|[*][*][^*]+?[*][*])+?)[*](?![*])/s',
        '_' => '/^_((?:[^_]|__[^_]*__)+?)_(?!_)\b/us',
    ];

    protected $textLevelElements = [
        'a',
        'br',
        'bdo',
        'abbr',
        'blink',
        'nextid',
        'acronym',
        'basefont',
        'b',
        'em',
        'big',
        'cite',
        'small',
        'spacer',
        'listing',
        'i',
        'rp',
        'del',
        'code',
        'strike',
        'marquee',
        'q',
        'rt',
        'ins',
        'font',
        'strong',
        's',
        'tt',
        'sub',
        'mark',
        'u',
        'xm',
        'sup',
        'nobr',
        'var',
        'ruby',
        'wbr',
        'span',
        'time',
    ];
}
