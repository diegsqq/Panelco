@extends('layouts.app')

@section('body-class', 'p3-no-scroll')
@section('full-screen', true)
@section('full-screen-col', 'col-12')

@section('content')
{{-- ============================================================
       Word-style document editor
       Ribbon tabs, paged canvas, status bar
       ============================================================ --}}
<div class="p3-word-shell" id="wordShell">

    {{-- ── Title bar (like Word top strip) ────────────────────── --}}
    <div class="p3-word-titlebar d-flex align-items-center gap-2 px-3 py-1">
        <button type="button" class="p3-word-file-btn p3-word-btn-sm" aria-label="File menu">
            <i class="bi bi-file-earmark-text me-1"></i>File
        </button>
        <div class="p3-word-ribbon-tabs d-flex gap-1 flex-nowrap overflow-hidden">
            <button type="button" class="p3-word-ribbon-tab is-active" role="tab" aria-selected="true">Home</button>
            <button type="button" class="p3-word-ribbon-tab" role="tab" aria-selected="false">Insert</button>
            <button type="button" class="p3-word-ribbon-tab" role="tab" aria-selected="false">Design</button>
            <button type="button" class="p3-word-ribbon-tab" role="tab" aria-selected="false">Layout</button>
            <button type="button" class="p3-word-ribbon-tab" role="tab" aria-selected="false">References</button>
            <button type="button" class="p3-word-ribbon-tab" role="tab" aria-selected="false">Mailings</button>
            <button type="button" class="p3-word-ribbon-tab" role="tab" aria-selected="false">Review</button>
            <button type="button" class="p3-word-ribbon-tab" role="tab" aria-selected="false">View</button>
            <button type="button" class="p3-word-ribbon-tab" role="tab" aria-selected="false">Help</button>
            <button type="button" class="p3-word-ribbon-tab" role="tab" aria-selected="false">Acrobat</button>
        </div>
        <div class="p3-word-tellme flex-grow-1 min-w-0">
            <span class="p3-tellme-label small text-muted"><i class="bi bi-lightbulb me-1"></i>Tell me what you want to do</span>
            <input type="text" id="tellMeInput" class="p3-tellme-input form-control form-control-sm" placeholder="Search commands…">
        </div>
        <div class="p3-word-actions d-flex align-items-center gap-1">
            <button type="button" class="p3-word-btn-sm p3-word-text-btn" title="Sign in"><i class="bi bi-person-circle me-1"></i>Sign in</button>
            <button type="button" class="p3-word-btn-sm p3-word-share-btn" id="shareBtn" title="Share"><i class="bi bi-share me-1"></i>Share</button>
        </div>
    </div>

    {{-- ── Ribbon (Home tab) ───────────────────────────────────── --}}
    <div class="p3-word-ribbon" id="wordRibbon">
        <div class="p3-word-ribbon-content p3-ribbon-tab-content is-active" id="ribbonHome" role="tabpanel">

            {{-- Clipboard --}}
            <div class="p3-word-ribbon-group">
                <div class="p3-word-ribbon-group-title">CLIPBOARD</div>
                <div class="p3-word-ribbon-controls">
                    <div class="p3-word-ribbon-btn-group">
                        <button type="button" class="p3-word-ribbon-btn p3-word-ribbon-btn-split" title="Paste (Ctrl+V)" aria-label="Paste">
                            <i class="bi bi-clipboard"></i><span class="p3-ribbon-label">Paste</span>
                        </button>
                        <button type="button" class="p3-word-ribbon-btn p3-word-ribbon-btn-split-arrow" title="Paste options" aria-label="Paste options"><i class="bi bi-chevron-down"></i></button>
                    </div>
                    <div class="p3-word-ribbon-sep"></div>
                    <button type="button" class="p3-word-ribbon-btn" title="Cut (Ctrl+X)" aria-label="Cut"><i class="bi bi-scissors"></i><span class="p3-ribbon-label">Cut</span></button>
                    <button type="button" class="p3-word-ribbon-btn" title="Copy (Ctrl+C)" aria-label="Copy"><i class="bi bi-clipboard-copy"></i><span class="p3-ribbon-label">Copy</span></button>
                    <div class="p3-word-ribbon-sep"></div>
                    <button type="button" class="p3-word-ribbon-btn" title="Format Painter" aria-label="Format Painter"><i class="bi bi-palette"></i><span class="p3-ribbon-label small">Format Painter</span></button>
                </div>
            </div>
            <div class="p3-word-ribbon-group-sep"></div>

            {{-- Font --}}
            <div class="p3-word-ribbon-group">
                <div class="p3-word-ribbon-group-title">FONT</div>
                <div class="p3-word-ribbon-controls">
                    <select id="fontFamily" class="p3-word-font-select" title="Font family" aria-label="Font family">
                        <option value="+body" selected>Calibri (Body)</option>
                        <option value="">Inter</option>
                        <option value="Times New Roman">Times New Roman</option>
                        <option value="Arial">Arial</option>
                        <option value="Georgia">Georgia</option>
                        <option value="Courier New">Courier New</option>
                    </select>
                    <select id="fontSize" class="p3-word-font-select" title="Font size" aria-label="Font size">
                        <option value="12" selected>12</option>
                        <option value="8">8</option><option value="9">9</option><option value="10">10</option>
                        <option value="11">11</option><option value="14">14</option><option value="16">16</option>
                        <option value="18">18</option><option value="20">20</option><option value="24">24</option>
                        <option value="26">26</option><option value="28">28</option><option value="36">36</option>
                        <option value="48">48</option><option value="72">72</option>
                    </select>
                    <div class="p3-word-ribbon-sep"></div>
                    <button type="button" class="p3-word-ribbon-btn" data-cmd="bold" title="Bold (Ctrl+B)" aria-label="Bold"><strong>B</strong></button>
                    <button type="button" class="p3-word-ribbon-btn" data-cmd="italic" title="Italic (Ctrl+I)" aria-label="Italic"><em>I</em></button>
                    <button type="button" class="p3-word-ribbon-btn" data-cmd="underline" title="Underline (Ctrl+U)" aria-label="Underline"><u>U</u></button>
                    <button type="button" class="p3-word-ribbon-btn" data-cmd="strikeThrough" title="Strikethrough" aria-label="Strikethrough"><span style="text-decoration:line-through">abc</span></button>
                    <button type="button" class="p3-word-ribbon-btn" title="Subscript" aria-label="Subscript"><sub>x₂</sub></button>
                    <button type="button" class="p3-word-ribbon-btn" title="Superscript" aria-label="Superscript"><sup>x²</sup></button>
                    <div class="p3-word-ribbon-sep"></div>
                    <button type="button" class="p3-word-ribbon-btn p3-word-color-picker" title="Font Color" aria-label="Font Color" style="cursor:pointer;position:relative">
                        <span style="color:#dc3545">A</span><i class="bi bi-caret-down-fill ms-1" style="font-size:.55rem"></i>
                        <input type="color" id="textColor" value="#000000" class="d-none" aria-label="Font color">
                    </button>
                    <button type="button" class="p3-word-ribbon-btn p3-word-highlight-picker" title="Text Highlight Color" aria-label="Text Highlight" style="cursor:pointer;position:relative">
                        <i class="bi bi-pencil-square"></i><i class="bi bi-caret-down-fill ms-1" style="font-size:.55rem"></i>
                        <input type="color" id="hlColor" value="#ffff00" class="d-none" aria-label="Highlight color">
                    </button>
                    <button type="button" class="p3-word-ribbon-btn" data-cmd="removeFormat" title="Clear All Formatting" aria-label="Clear All Formatting"><span class="p3-ribbon-label small">Clear All</span></button>
                </div>
            </div>
            <div class="p3-word-ribbon-group-sep"></div>

            {{-- Paragraph --}}
            <div class="p3-word-ribbon-group">
                <div class="p3-word-ribbon-group-title">PARAGRAPH</div>
                <div class="p3-word-ribbon-controls">
                    <div class="p3-word-align-group">
                        <button type="button" class="p3-word-ribbon-btn p3-word-align-btn is-active" data-cmd="justifyLeft" title="Align Left (Ctrl+L)" aria-label="Align Left"><i class="bi bi-text-left"></i></button>
                        <button type="button" class="p3-word-ribbon-btn p3-word-align-btn" data-cmd="justifyCenter" title="Center (Ctrl+E)" aria-label="Center"><i class="bi bi-text-center"></i></button>
                        <button type="button" class="p3-word-ribbon-btn p3-word-align-btn" data-cmd="justifyRight" title="Align Right (Ctrl+R)" aria-label="Align Right"><i class="bi bi-text-right"></i></button>
                        <button type="button" class="p3-word-ribbon-btn p3-word-align-btn" data-cmd="justifyFull" title="Justify (Ctrl+J)" aria-label="Justify"><i class="bi bi-text-justify"></i></button>
                    </div>
                    <div class="p3-word-ribbon-sep"></div>
                    <button type="button" class="p3-word-ribbon-btn" data-cmd="insertUnorderedList" title="Bullets" aria-label="Bullets"><i class="bi bi-list-ul"></i></button>
                    <button type="button" class="p3-word-ribbon-btn" data-cmd="insertOrderedList" title="Numbering" aria-label="Numbering"><i class="bi bi-list-ol"></i></button>
                    <button type="button" class="p3-word-ribbon-btn" title="Decrease Indent" aria-label="Decrease Indent" data-cmd="outdent"><i class="bi bi-text-indent-left"></i></button>
                    <button type="button" class="p3-word-ribbon-btn" title="Increase Indent" aria-label="Increase Indent" data-cmd="indent"><i class="bi bi-text-indent-right"></i></button>
                    <div class="p3-word-ribbon-sep"></div>
                    <button type="button" class="p3-word-ribbon-btn" title="Sort" aria-label="Sort" data-cmd="insertOrderedList"><i class="bi bi-arrow-down-up"></i></button>
                    <button type="button" class="p3-word-ribbon-btn" title="Show/Hide ¶" aria-label="Show paragraph marks">¶</button>
                </div>
            </div>
            <div class="p3-word-ribbon-group-sep"></div>

            {{-- Styles --}}
            <div class="p3-word-ribbon-group">
                <div class="p3-word-ribbon-group-title">STYLES</div>
                <div class="p3-word-ribbon-controls p3-word-styles">
                    <button type="button" class="p3-word-style-btn is-active" data-style="p" title="Normal">Normal</button>
                    <button type="button" class="p3-word-style-btn" data-style="p" title="No Spacing">No Spacing</button>
                    <button type="button" class="p3-word-style-btn" data-style="h1" title="Heading 1">Heading 1</button>
                    <button type="button" class="p3-word-style-btn" data-style="h2" title="Heading 2">Heading 2</button>
                    <button type="button" class="p3-word-style-btn" data-style="h3" title="Heading 3">Heading 3</button>
                    <button type="button" class="p3-word-style-btn" data-style="title" title="Title">Title</button>
                    <button type="button" class="p3-word-style-btn" data-style="subtitle" title="Subtitle">Subtitle</button>
                    <button type="button" class="p3-word-style-btn" data-style="strong" title="Strong">Strong</button>
                    <button type="button" class="p3-word-style-btn" data-style="em" title="Emphasis">Emphasis</button>
                    <button type="button" class="p3-word-style-btn" data-style="blockquote" title="Quote">Quote</button>
                </div>
            </div>
            <div class="p3-word-ribbon-group-sep"></div>

            {{-- Editing --}}
            <div class="p3-word-ribbon-group">
                <div class="p3-word-ribbon-group-title">EDITING</div>
                <div class="p3-word-ribbon-controls">
                    <button type="button" class="p3-word-ribbon-btn" id="findBtn" title="Find (Ctrl+F)" aria-label="Find"><i class="bi bi-search me-1"></i><span class="p3-ribbon-label">Find</span></button>
                    <button type="button" class="p3-word-ribbon-btn" id="replaceBtnRibbon" title="Replace (Ctrl+H)" aria-label="Replace"><span class="p3-ribbon-label">Replace</span></button>
                    <button type="button" class="p3-word-ribbon-btn" id="selectBtn" title="Select" aria-label="Select"><span class="p3-ribbon-label">Select</span></button>
                </div>
            </div>

        </div>

        {{-- Other ribbon tabs (hidden content) --}}
        <div class="p3-word-ribbon-content d-none" id="ribbonInsert" role="tabpanel">

            {{-- Tables --}}
            <div class="p3-word-ribbon-group">
                <div class="p3-word-ribbon-group-title">Tables</div>
                <div class="p3-word-ribbon-controls">
                    <button type="button" class="p3-word-ribbon-btn p3-word-ribbon-btn-large" id="tableBtnWord" title="Insert Table" aria-label="Insert table">
                        <i class="bi bi-grid-fill"></i><span class="p3-ribbon-label">Table</span>
                    </button>
                </div>
            </div>

            {{-- Illustrations --}}
            <div class="p3-word-ribbon-group">
                <div class="p3-word-ribbon-group-title">Illustrations</div>
                <div class="p3-word-ribbon-controls">
                    <button type="button" class="p3-word-ribbon-btn" id="imageBtnWord" title="Pictures" aria-label="Insert picture"><i class="bi bi-image me-1"></i><span class="p3-ribbon-label">Pictures</span></button>
                    <button type="button" class="p3-word-ribbon-btn" id="shapesBtnWord" title="Shapes" aria-label="Insert shapes"><i class="bi bi-patch-plus me-1"></i><span class="p3-ribbon-label">Shapes</span></button>
                    <button type="button" class="p3-word-ribbon-btn" id="iconsBtnWord" title="Icons" aria-label="Insert icons"><i class="bi bi-emoji-smile me-1"></i><span class="p3-ribbon-label">Icons</span></button>
                    <button type="button" class="p3-word-ribbon-btn" id="smartartBtnWord" title="SmartArt" aria-label="Insert SmartArt"><i class="bi bi-diagram-3 me-1"></i><span class="p3-ribbon-label">SmartArt</span></button>
                    <button type="button" class="p3-word-ribbon-btn" id="chartBtnWord" title="Chart" aria-label="Insert chart"><i class="bi bi-bar-chart me-1"></i><span class="p3-ribbon-label">Chart</span></button>
                    <button type="button" class="p3-word-ribbon-btn" id="screenshotBtnWord" title="Screenshot" aria-label="Insert screenshot"><i class="bi bi-camera-fill me-1"></i><span class="p3-ribbon-label">Screenshot</span></button>
                </div>
            </div>

            {{-- Links --}}
            <div class="p3-word-ribbon-group">
                <div class="p3-word-ribbon-group-title">Links</div>
                <div class="p3-word-ribbon-controls">
                    <button type="button" class="p3-word-ribbon-btn" id="linkBtnWord" title="Link (Ctrl+K)" aria-label="Insert link"><i class="bi bi-link-45deg me-1"></i><span class="p3-ribbon-label">Link</span></button>
                    <button type="button" class="p3-word-ribbon-btn" id="bookmarkBtnWord" title="Bookmark" aria-label="Insert bookmark"><i class="bi bi-bookmark me-1"></i><span class="p3-ribbon-label">Bookmark</span></button>
                    <button type="button" class="p3-word-ribbon-btn" id="crossrefBtnWord" title="Cross-reference" aria-label="Insert cross-reference"><i class="bi bi-arrow-repeat me-1"></i><span class="p3-ribbon-label">Cross-reference</span></button>
                </div>
            </div>

            {{-- Comments --}}
            <div class="p3-word-ribbon-group">
                <div class="p3-word-ribbon-group-title">Comments</div>
                <div class="p3-word-ribbon-controls">
                    <button type="button" class="p3-word-ribbon-btn" id="commentBtnWord" title="New Comment" aria-label="New comment"><i class="bi bi-chat-square-text me-1"></i><span class="p3-ribbon-label">Comment</span></button>
                </div>
            </div>

            {{-- Header & Footer --}}
            <div class="p3-word-ribbon-group">
                <div class="p3-word-ribbon-group-title">Header & Footer</div>
                <div class="p3-word-ribbon-controls">
                    <button type="button" class="p3-word-ribbon-btn" id="headerBtnWord" title="Header" aria-label="Insert header"><i class="bi bi-layout-header me-1"></i><span class="p3-ribbon-label">Header</span></button>
                    <button type="button" class="p3-word-ribbon-btn" id="footerBtnWord" title="Footer" aria-label="Insert footer"><i class="bi bi-layout-footer me-1"></i><span class="p3-ribbon-label">Footer</span></button>
                    <button type="button" class="p3-word-ribbon-btn" id="pageNumBtnWord" title="Page Number" aria-label="Insert page number"><i class="bi bi-hashtag me-1"></i><span class="p3-ribbon-label">Page Number</span></button>
                </div>
            </div>

            {{-- Text --}}
            <div class="p3-word-ribbon-group">
                <div class="p3-word-ribbon-group-title">Text</div>
                <div class="p3-word-ribbon-controls">
                    <button type="button" class="p3-word-ribbon-btn" id="textBoxBtnWord" title="Text Box" aria-label="Insert text box"><i class="bi bi-textarea me-1"></i><span class="p3-ribbon-label">Text Box</span></button>
                    <button type="button" class="p3-word-ribbon-btn" id="quickPartsBtnWord" title="Quick Parts" aria-label="Insert quick parts"><i class="bi bi-patch-check me-1"></i><span class="p3-ribbon-label">Quick Parts</span></button>
                    <button type="button" class="p3-word-ribbon-btn" id="wordArtBtnWord" title="WordArt" aria-label="Insert wordart"><i class="bi bi-fonts me-1"></i><span class="p3-ribbon-label">WordArt</span></button>
                    <button type="button" class="p3-word-ribbon-btn" id="dropCapBtnWord" title="Drop Cap" aria-label="Insert drop cap"><span class="p3-ribbon-label">Drop Cap</span></button>
                    <button type="button" class="p3-word-ribbon-btn" id="dateTimeBtnWord" title="Date & Time" aria-label="Insert date and time"><i class="bi bi-calendar-event me-1"></i><span class="p3-ribbon-label">Date & Time</span></button>
                    <button type="button" class="p3-word-ribbon-btn" id="objectBtnWord" title="Object" aria-label="Insert object"><i class="bi bi-file-earmark me-1"></i><span class="p3-ribbon-label">Object</span></button>
                </div>
            </div>

            {{-- Symbols --}}
            <div class="p3-word-ribbon-group">
                <div class="p3-word-ribbon-group-title">Symbols</div>
                <div class="p3-word-ribbon-controls">
                    <button type="button" class="p3-word-ribbon-btn" id="equationBtnWord" title="Equation" aria-label="Insert equation"><span class="p3-ribbon-label">π</span><span class="p3-ribbon-label small">Equation</span></button>
                    <button type="button" class="p3-word-ribbon-btn" id="symbolBtnWord" title="Symbol" aria-label="Insert symbol"><span class="p3-ribbon-label">Ω</span><span class="p3-ribbon-label small">Symbol</span></button>
                </div>
            </div>

        </div>

        {{-- ── Design tab: Document Formatting / Themes ────────────────────── --}}
        <div class="p3-word-ribbon-content d-none" id="ribbonDesign" role="tabpanel">

            {{-- Document Themes --}}
            <div class="p3-word-ribbon-group">
                <div class="p3-word-ribbon-group-title">Document Formatting</div>
                <div class="p3-word-ribbon-controls p3-word-theme-controls">
                    <button type="button" class="p3-word-theme-btn p3-theme-dropdown" id="themesBtn" title="Themes" aria-label="Document themes">
                        <span class="p3-theme-preview"></span><span class="p3-ribbon-label">Themes</span><i class="bi bi-chevron-down ms-2"></i>
                    </button>
                    <div class="p3-theme-menu d-none" id="themesMenu">
                        <div class="p3-theme-header">Office</div>
                        <div class="p3-theme-grid" id="themeGrid">
                            {{-- Theme thumbnails --}}
                            <button class="p3-theme-tile p3-theme-selected" title="Office" data-theme="office"><div class="p3-theme-swatch"></div><span class="p3-theme-name">Office</span></button>
                            <button class="p3-theme-tile" title="Facet" data-theme="facet"><div class="p3-theme-swatch p3-swatch-facet"></div><span class="p3-theme-name">Facet</span></button>
                            <button class="p3-theme-tile" title="Gallery" data-theme="gallery"><div class="p3-theme-swatch p3-swatch-gallery"></div><span class="p3-theme-name">Gallery</span></button>
                            <button class="p3-theme-tile" title="Integral" data-theme="integral"><div class="p3-theme-swatch p3-swatch-integral"></div><span class="p3-theme-name">Integral</span></button>
                            <button class="p3-theme-tile" title="Ion" data-theme="ion"><div class="p3-theme-swatch p3-swatch-ion"></div><span class="p3-theme-name">Ion</span></button>
                            <button class="p3-theme-tile" title="Ion Boardroom" data-theme="ion-boardroom"><div class="p3-theme-swatch p3-swatch-ionb"></div><span class="p3-theme-name">Ion Boardroom</span></button>
                            <button class="p3-theme-tile" title="Organic" data-theme="organic"><div class="p3-theme-swatch p3-swatch-organic"></div><span class="p3-theme-name">Organic</span></button>
                            <button class="p3-theme-tile" title="Retrospect" data-theme="retrospect"><div class="p3-theme-swatch p3-swatch-retro"></div><span class="p3-theme-name">Retrospect</span></button>
                            <button class="p3-theme-tile" title="Slice" data-theme="slice"><div class="p3-theme-swatch p3-swatch-slice"></div><span class="p3-theme-name">Slice</span></button>
                            <button class="p3-theme-tile" title="Wisp" data-theme="wisp"><div class="p3-theme-swatch p3-swatch-wisp"></div><span class="p3-theme-name">Wisp</span></button>
                            <button class="p3-theme-tile" title="Banded" data-theme="banded"><div class="p3-theme-swatch p3-swatch-banded"></div><span class="p3-theme-name">Banded</span></button>
                            <button class="p3-theme-tile" title="Basis" data-theme="basis"><div class="p3-theme-swatch p3-swatch-basis"></div><span class="p3-theme-name">Basis</span></button>
                            <button class="p3-theme-tile" title="Berlin" data-theme="berlin"><div class="p3-theme-swatch p3-swatch-berlin"></div><span class="p3-theme-name">Berlin</span></button>
                            <button class="p3-theme-tile" title="Circuit" data-theme="circuit"><div class="p3-theme-swatch p3-swatch-circuit"></div><span class="p3-theme-name">Circuit</span></button>
                            <button class="p3-theme-tile" title="Damask" data-theme="damask"><div class="p3-theme-swatch p3-swatch-damask"></div><span class="p3-theme-name">Damask</span></button>
                            <button class="p3-theme-tile" title="Dividend" data-theme="dividend"><div class="p3-theme-swatch p3-swatch-dividend"></div><span class="p3-theme-name">Dividend</span></button>
                            <button class="p3-theme-tile" title="Droplet" data-theme="droplet"><div class="p3-theme-swatch p3-swatch-droplet"></div><span class="p3-theme-name">Droplet</span></button>
                            <button class="p3-theme-tile" title="Frame" data-theme="frame"><div class="p3-theme-swatch p3-swatch-frame"></div><span class="p3-theme-name">Frame</span></button>
                            <button class="p3-theme-tile" title="Gallery" data-theme="gallery2"><div class="p3-theme-swatch p3-swatch-gallery2"></div><span class="p3-theme-name">Gallery</span></button>
                            <button class="p3-theme-tile" title="Main Event" data-theme="main-event"><div class="p3-theme-swatch p3-swatch-main"></div><span class="p3-theme-name">Main Event</span></button>
                        </div>
                        <div class="p3-theme-footer">
                            <a class="p3-theme-link">Reset to Theme from Template</a>
                            <a class="p3-theme-link">Browse for Themes…</a>
                            <a class="p3-theme-link">Save Current Theme…</a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Colors / Fonts / Effects --}}
            <div class="p3-word-ribbon-group">
                <div class="p3-word-ribbon-group-title">Theme Options</div>
                <div class="p3-word-ribbon-controls">
                    <div class="p3-word-color-group">
                        <button type="button" class="p3-word-ribbon-btn p3-word-ribbon-btn-color" id="colorsBtn" title="Colors" aria-label="Theme colors">
                            <span class="p3-color-chip" style="background:#2b579a"></span><span class="p3-ribbon-label small">Colors</span><i class="bi bi-chevron-down ms-1"></i>
                        </button>
                        <button type="button" class="p3-word-ribbon-btn p3-word-ribbon-btn-font" id="fontsBtn" title="Fonts" aria-label="Theme fonts">
                            <span class="p3-ribbon-label small">Fonts</span><i class="bi bi-chevron-down ms-1"></i>
                        </button>
                        <button type="button" class="p3-word-ribbon-btn" id="effectsBtn" title="Effects" aria-label="Theme effects"><span class="p3-ribbon-label small">Effects</span><i class="bi bi-chevron-down ms-1"></i></button>
                        <button type="button" class="p3-word-ribbon-btn" id="paraSpacingBtn" title="Paragraph Spacing" aria-label="Paragraph spacing"><span class="p3-ribbon-label small">Paragraph Spacing</span><i class="bi bi-chevron-down ms-1"></i></button>
                    </div>
                </div>
            </div>

            {{-- Page Background --}}
            <div class="p3-word-ribbon-group">
                <div class="p3-word-ribbon-group-title">Page Background</div>
                <div class="p3-word-ribbon-controls">
                    <button type="button" class="p3-word-ribbon-btn" id="watermarkBtn" title="Watermark" aria-label="Watermark"><i class="bi bi-cloud-fog me-1"></i><span class="p3-ribbon-label">Watermark</span></button>
                    <button type="button" class="p3-word-ribbon-btn" id="pageColorBtn" title="Page Color" aria-label="Page color"><i class="bi bi-paint-bucket me-1"></i><span class="p3-ribbon-label">Page Color</span></button>
                    <button type="button" class="p3-word-ribbon-btn" id="pageBordersBtn" title="Page Borders" aria-label="Page borders"><i class="bi bi-layout-border me-1"></i><span class="p3-ribbon-label">Page Borders</span></button>
                </div>
            </div>

        </div>
    </div>

    {{-- ── Editing area ────────────────────────────────────────── --}}
    <div class="p3-word-editor">
        {{-- Left gutter (grey) --}}
        <div class="p3-word-gutter"></div>

        {{-- Centre canvas --}}
        <div class="p3-word-canvas">
            <div class="p3-word-pages-wrap" id="wordPagesWrap">
                <div class="p3-word-page" id="wordPage">

                    {{-- Header band --}}
                    <div class="p3-word-page-band p3-word-page-band-header" id="wordHeaderBand">
                        <div class="p3-word-band-label">Header</div>
                        <div id="wordHeader"
                             class="p3-doc-region p3-doc-header"
                             contenteditable="true"
                             role="textbox"
                             aria-label="Page header"
                             data-placeholder="Type the header text here">{!! app(\App\Services\DocumentContentSanitizer::class)->sanitize($document->content_header) !!}</div>
                    </div>

                    {{-- Body --}}
                    <div class="p3-word-page-body">
                        <div id="wordBody"
                             class="p3-doc-region p3-doc-body"
                             contenteditable="true"
                             role="textbox"
                             aria-multiline="true"
                             aria-label="Document body"
                             data-placeholder="Type here…">{!! app(\App\Services\DocumentContentSanitizer::class)->sanitize($document->content) !!}</div>
                    </div>

                    {{-- Footer band --}}
                    <div class="p3-word-page-band p3-word-page-band-footer" id="wordFooterBand">
                        <div class="p3-word-band-label">Footer</div>
                        <div id="wordFooter"
                             class="p3-doc-region p3-doc-footer"
                             contenteditable="true"
                             role="textbox"
                             aria-label="Page footer"
                             data-placeholder="Type the footer text here">{!! app(\App\Services\DocumentContentSanitizer::class)->sanitize($document->content_footer) !!}</div>
                        <div class="p3-word-page-num">Page 1 of 1</div>
                    </div>

                </div>
            </div>
        </div>

        {{-- Right gutter (grey) --}}
        <div class="p3-word-gutter"></div>
    </div>

    {{-- ── Status bar ──────────────────────────────────────────── --}}
    <div class="p3-word-statusbar">
        <div class="p3-status-left">
            <span class="p3-status-item">Page 1 of 1</span>
            <span class="p3-status-item" id="wordStatusWords">0 words</span>
            <span class="p3-status-sep"></span>
            <span class="p3-status-item small text-muted">English (Philippines)</span>
            <span class="p3-status-sep"></span>
            <span class="p3-status-item small text-muted" id="wordStatusAccessibility"><i class="bi bi-person-check me-1"></i>Accessibility: Good to go</span>
        </div>
        <div class="p3-status-right">
            <div class="p3-status-view-btns">
                <button type="button" class="p3-status-view-btn is-active" title="Read Mode"><i class="bi bi-journal-text"></i></button>
                <button type="button" class="p3-status-view-btn" title="Print Layout"><i class="bi bi-file-earmark-text"></i></button>
                <button type="button" class="p3-status-view-btn" title="Web Layout"><i class="bi bi-browser-chrome"></i></button>
            </div>
            <span class="p3-status-zoom"><span id="wordStatusZoom">117%</span></span>
            <input type="range" class="p3-status-zoom-slider" id="wordZoomSlider" min="60" max="150" value="117">
            <button type="button" class="p3-word-btn-sm p3-word-text-btn" title="+"><i class="bi bi-plus"></i></button>
            <button type="button" class="p3-word-btn-sm p3-word-text-btn" title="-"><i class="bi bi-dash"></i></button>
        </div>
    </div>
</div>

{{-- Hidden inputs --}}
<input type="file" id="imageInputWord" accept="image/png,image/jpeg,image/gif,image/webp" class="d-none" aria-hidden="true" tabindex="-1">

{{-- Find & Replace modal --}}
<div class="modal fade" id="findReplaceModalWord" tabindex="-1" aria-labelledby="findReplaceWordLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title" id="findReplaceWordLabel">Find and replace</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2"><input type="text" id="findInputWord" class="form-control form-control-sm" placeholder="Find…"></div>
                <div class="mb-2"><input type="text" id="replaceInputWord" class="form-control form-control-sm" placeholder="Replace with…"></div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" id="matchCaseWord">
                    <label class="form-check-label small" for="matchCaseWord">Match case</label>
                </div>
                <div class="d-flex gap-1 mt-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="findNextBtnWord">Find next</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="replaceBtnWord">Replace</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="replaceAllBtnWord">Replace all</button>
                </div>
                <div id="findStatusWord" class="small text-muted mt-2"></div>
            </div>
        </div>
    </div>
</div>

<style>
/* ── Word shell ─────────────────────────────────────────────────── */
.p3-word-shell {
    display: flex;
    flex-direction: column;
    height: 100vh;
    background: transparent;
    overflow: hidden;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
}
.p3-word-shell * { box-sizing: border-box; }

/* ── Title bar ──────────────────────────────────────────────────── */
.p3-word-titlebar {
    background: #f2f2f2;
    border-bottom: 1px solid #c4c4c4;
    flex-shrink: 0;
    color: #333;
    font-size: .85rem;
}
.p3-word-file-btn {
    background: transparent; border: 1px solid transparent; border-radius: 2px;
    color: #333; font-weight: 600; cursor: pointer; padding: 2px 6px;
    font-size: .85rem; display: inline-flex; align-items: center; gap: 4px;
}
.p3-word-file-btn:hover { background: #e7e6e6; border-color: #a6a6a6; }
.p3-word-ribbon-tabs { }
.p3-word-ribbon-tab {
    background: transparent; border: 1px solid transparent; border-bottom: 2px solid transparent;
    color: #666; font-weight: 400; font-size: .85rem; padding: 4px 8px;
    cursor: pointer; white-space: nowrap; line-height: 1.2;
}
.p3-word-ribbon-tab:hover { background: #e7e6e6; color: #333; border-color: transparent; }
.p3-word-ribbon-tab.is-active { color: #000; font-weight: 600; border-bottom-color: #2b579a; }

.p3-word-tellme { display:flex; align-items:center; gap:4px; padding: 2px 0; }
.p3-tellme-label { font-size: .75rem; color: #888; }
.p3-tellme-input { width: 120px; font-size: .75rem; padding: 1px 6px; border: 1px solid #a6a6a6; border-radius: 2px; background: #fff; }
.p3-tellme-input:focus { border-color: #2b579a; outline: none; box-shadow: 0 0 0 1px #2b579a; }
.p3-tellme-row:hover { background: #e7e6e6; color: #000; }
[data-theme="dark"] .p3-tellme-input { background: #3f3f3f; color: #fff; border-color: #5a5a5a; }
[data-theme="dark"] .p3-tellme-row:hover { background: #3f3f3f; color: #fff; }

.p3-word-actions { padding: 1px 0; }
.p3-word-btn-sm {
    background: transparent; border: 1px solid transparent; border-radius: 2px;
    color: #333; font-size: .78rem; padding: 2px 8px; cursor: pointer;
    display: inline-flex; align-items: center; gap: 4px;
}
.p3-word-btn-sm:hover { background: #e7e6e6; border-color: #a6a6a6; }
.p3-word-share-btn { background: #fff; border-color: #a6a6a6; color: #333; font-weight: 600; }

/* ── Ribbon ─────────────────────────────────────────────────────── */
.p3-word-ribbon {
    background: #fff;
    border-bottom: 1px solid #c4c4c4;
    flex-shrink: 0;
    overflow-x: auto;
    padding: 0 4px;
}
.p3-word-ribbon-content { display: flex; gap: 8px; padding: 4px 0; min-width: 100%; }
.p3-word-ribbon-group {
    display: flex; flex-direction: column; gap: 2px;
    min-width: 150px;
}
.p3-word-ribbon-group-title {
    font-size: .68rem; font-weight: 700; color: #666;
    text-transform: uppercase; letter-spacing: .02em; padding: 0 4px 2px;
}
.p3-word-ribbon-controls {
    display: flex; flex-wrap: wrap; gap: 2px; padding: 0 4px;
}
.p3-word-ribbon-sep { width: 1px; height: 14px; background: #c4c4c4; margin: 1px 0; align-self: center; }
.p3-word-ribbon-group-sep { width: 1px; background: #c4c4c4; margin: 0 4px; align-self: stretch; flex-shrink: 0; }
.p3-word-ribbon-btn {
    background: transparent; border: 1px solid transparent; border-radius: 2px;
    color: #333; font-size: .78rem; padding: 2px 6px; cursor: pointer;
    display: inline-flex; align-items: center; gap: 3px; white-space: nowrap;
    line-height: 1.25;
}
.p3-word-ribbon-btn:hover { background: #f2f2f2; border-color: #a6a6a6; color: #000; }
.p3-word-ribbon-btn.is-active { background: #cce8ff; border-color: #2b579a; color: #000; font-weight: 600; }
.p3-word-ribbon-btn-large { min-width: 78px; justify-content: center; padding: 3px 8px; }

.p3-word-font-select { font-size: .78rem; padding: 1px 4px; border: 1px solid #a6a6a6; border-radius: 2px; background: #fff; }
.p3-word-font-select:focus { border-color: #2b579a; outline: none; box-shadow: 0 0 0 1px #2b579a; }

/* ── Split button (Paste with dropdown) ── */
.p3-word-ribbon-btn-group { display: inline-flex; gap: 0; }
.p3-word-ribbon-btn-split { border-top-right-radius: 0; border-bottom-right-radius: 0; padding-right: 6px; }
.p3-word-ribbon-btn-split-arrow { border-top-left-radius: 0; border-bottom-left-radius: 0; padding: 2px 5px; font-size: .7rem; color: #333; }
.p3-word-ribbon-btn-split-arrow:hover { background: #f2f2f2; border-color: #a6a6a6; color: #000; }

/* ── Font color / highlight picker dropdown arrow ── */
.p3-word-color-picker .bi-caret-down-fill,
.p3-word-highlight-picker .bi-caret-down-fill { color: #333; }
.p3-word-color-picker:hover .bi-caret-down-fill,
.p3-word-highlight-picker:hover .bi-caret-down-fill { color: #000; }

/* ── Clear All (text only, no icon) ── */
.p3-word-ribbon-btn[data-cmd="removeFormat"] { padding: 2px 6px; }

.p3-word-align-group { display: flex; gap: 1px; }
.p3-word-align-btn {
    min-width: 26px; justify-content: center; padding: 2px 4px; font-size: .8rem;
}
.p3-word-align-btn.is-active { background: #cce8ff; border-color: #2b579a; color: #000; font-weight: 600; }

.p3-word-styles { gap: 2px; }
.p3-word-style-btn {
    background: transparent; border: 1px solid transparent; border-radius: 2px;
    color: #333; font-size: .78rem; padding: 2px 6px; cursor: pointer;
    white-space: nowrap;
}
.p3-word-style-btn:hover { background: #f2f2f2; border-color: #a6a6a6; }
.p3-word-style-btn.is-active { background: #cce8ff; border-color: #2b579a; color: #000; font-weight: 600; }

.p3-word-color-picker i { font-size: 1.1rem; width: 16px; }
.p3-word-highlight-picker i { font-size: 1.1rem; width: 16px; }

/* ── Editor area ────────────────────────────────────────────────── */
.p3-word-editor {
    flex: 1 1 auto;
    min-height: 0;
    overflow: hidden;
    display: flex;
}
.p3-word-gutter {
    flex-shrink: 0;
    width: 60px;
    background: #b0b0b0;
}
.p3-word-canvas {
    flex: 1 1 auto;
    overflow: auto;
    background: #b0b0b0;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    padding-top: 10px;
}
.p3-word-pages-wrap { width: 100%; max-width: 940px; }
.p3-word-page {
    background: #fff;
    width: 210mm;            /* A4 width */
    min-height: 297mm;       /* A4 height */
    box-shadow: 0 0 10px rgba(0,0,0,.15);
    margin: 0 auto 10px;
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}
[data-theme="dark"] .p3-word-page { background: #1e1e1e; box-shadow: none; }

.p3-word-page-band {
    background: #f8f8f8;
    padding: 10px 1in;
    position: relative;
    font-size: .82rem;
    color: #555;
    border-bottom: 1px dashed #c4c4c4;
}
.p3-word-page-band-header { border-bottom-style: dashed; }
.p3-word-page-band-footer { border-top: 1px dashed #c4c4c4; border-bottom: none; }
.p3-word-band-label {
    font-size: .68rem; font-weight: 700; color: #888;
    text-transform: uppercase; letter-spacing: .04em; margin-bottom: 4px;
}
.p3-word-page-num {
    position: absolute; bottom: 12px; right: 1in;
    font-size: .75rem; color: #666;
}

.p3-word-page-body {
    padding: 1in;
    flex: 1 1 auto;
    position: relative;
}
.p3-doc-region { outline: none; line-height: 1.6; font-size: 11pt; min-height: 1.2em; position: relative; }
.p3-doc-region.is-empty::before { content: attr(data-placeholder); color: #aaaaaa; pointer-events: none; }
.p3-doc-body { min-height: 600px; font-size: 11pt; }
.p3-doc-region.p3-show-marks { position: relative; }
.p3-doc-region.p3-show-marks::after { content: ' ¶'; color: #999; font-size: .75rem; vertical-align: baseline; }

/* ── Status bar ─────────────────────────────────────────────────── */
.p3-word-statusbar {
    background: #f2f2f2;
    border-top: 1px solid #c4c4c4;
    flex-shrink: 0;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 10px;
    font-size: .75rem;
    color: #333;
}
.p3-status-left { display: flex; align-items: center; gap: 10px; }
.p3-status-item { white-space: nowrap; }
.p3-status-sep { width: 1px; height: 10px; background: #a6a6a6; margin: 0 6px; align-self: center; }
.p3-status-right { display: flex; align-items: center; gap: 4px; }
.p3-status-view-btns { display: flex; gap: 1px; }
.p3-status-view-btn {
    background: transparent; border: 1px solid #c4c4c4; border-radius: 0;
    color: #666; font-size: .75rem; padding: 1px 6px; cursor: pointer;
}
.p3-status-view-btn.is-active { background: #cce8ff; border-color: #2b579a; color: #000; }
.p3-status-zoom { white-space: nowrap; font-weight: 600; }
.p3-status-zoom-slider { width: 70px; height: 6px; margin: 0 4px; -webkit-appearance: none; background: #c4c4c4; border-radius: 3px; }
.p3-status-zoom-slider::-webkit-slider-thumb { -webkit-appearance: none; width: 12px; height: 12px; background: #2b579a; border-radius: 50%; cursor: pointer; }

/* ── Editable content styles ────────────────────────────────────── */
.p3-doc-body h1 { font-size: 1.5rem; font-weight: 700; margin: 1rem 0; }
.p3-doc-body h2 { font-size: 1.15rem; font-weight: 700; margin: 1rem 0; }
.p3-doc-body h3 { font-size: 1rem; font-weight: 600; margin: .8rem 0; }
.p3-doc-body p { margin: 0 0 .8rem; }
.p3-doc-body ul, .p3-doc-body ol { padding-left: 1.4rem; margin: .5rem 0; }

/* ── Toolbar buttons (existing toolbar retained) ────────────────── */
.p3-editor-btn {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 2rem; height: 2rem; padding: 0;
    background: transparent; color: inherit;
    border: 1px solid transparent; border-radius: .4rem;
    font-size: .88rem; cursor: pointer; vertical-align: middle;
}
.p3-editor-btn:hover { background: #f0f9f3; border-color: var(--p3-card-border); }

/* ── Dark mode ──────────────────────────────────────────────────── */
[data-theme="dark"] .p3-word-shell { background: transparent; color: #ccc; }
[data-theme="dark"] .p3-word-titlebar { background: #2d2d2d; border-color: #3f3f3f; color: #ccc; }
[data-theme="dark"] .p3-word-ribbon { background: #2d2d2d; border-color: #3f3f3f; }
[data-theme="dark"] .p3-word-ribbon-tab { color: #ccc; }
[data-theme="dark"] .p3-word-ribbon-tab.is-active { color: #fff; border-bottom-color: #3794ff; }
[data-theme="dark"] .p3-word-ribbon-btn { color: #ccc; }
[data-theme="dark"] .p3-word-ribbon-btn:hover { background: #3f3f3f; border-color: #5a5a5a; color: #fff; }
[data-theme="dark"] .p3-word-ribbon-btn.is-active { background: #004488; border-color: #3794ff; color: #fff; }
[data-theme="dark"] .p3-word-ribbon-btn-split-arrow { color: #ccc; }
[data-theme="dark"] .p3-word-ribbon-btn-split-arrow:hover { background: #3f3f3f; border-color: #5a5a5a; color: #fff; }
[data-theme="dark"] .p3-word-color-picker .bi-caret-down-fill,
[data-theme="dark"] .p3-word-highlight-picker .bi-caret-down-fill { color: #ccc; }
[data-theme="dark"] .p3-word-color-picker:hover .bi-caret-down-fill,
[data-theme="dark"] .p3-word-highlight-picker:hover .bi-caret-down-fill { color: #fff; }
[data-theme="dark"] .p3-word-ribbon-group-title { color: #888; }
[data-theme="dark"] .p3-word-ribbon-sep { background: #3f3f3f; }
[data-theme="dark"] .p3-word-ribbon-group-sep { background: #3f3f3f; }
[data-theme="dark"] .p3-word-font-select { background: #3f3f3f; color: #fff; border-color: #5a5a5a; }
[data-theme="dark"] .p3-word-gutter { background: #333; }
[data-theme="dark"] .p3-word-canvas { background: #333; }
[data-theme="dark"] .p3-word-page { background: #1e1e1e; box-shadow: none; }
[data-theme="dark"] .p3-word-page-band { background: #252525; color: #aaa; border-color: #3f3f3f; }
[data-theme="dark"] .p3-word-statusbar { background: #2d2d2d; border-color: #3f3f3f; color: #ccc; }
[data-theme="dark"] .p3-status-sep { background: #5a5a5a; }
[data-theme="dark"] .p3-status-view-btn { background: transparent; border-color: #5a5a5a; color: #ccc; }
[data-theme="dark"] .p3-status-view-btn.is-active { background: #004488; border-color: #3794ff; color: #fff; }
[data-theme="dark"] .p3-doc-region.is-empty::before { color: #666; }
[data-theme="dark"] .p3-doc-region.p3-show-marks::after { color: #666; }

/* ── Design tab: Document Themes ─────────────────────────────────────── */
.p3-word-theme-controls { display: flex; align-items: center; gap: 4px; padding: 0 4px; }
.p3-theme-preview { width: 34px; height: 22px; background: #fff; border: 1px solid #a6a6a6; border-radius: 2px; box-shadow: 0 1px 2px rgba(0,0,0,.15); }
.p3-theme-dropdown {
    background: transparent; border: 1px solid transparent; border-radius: 2px;
    color: #333; font-size: .78rem; padding: 2px 6px; cursor: pointer;
    display: inline-flex; align-items: center; gap: 4px; white-space: nowrap;
}
.p3-theme-dropdown:hover { background: #f2f2f2; border-color: #a6a6a6; }
.p3-theme-dropdown.is-active { background: #cce8ff; border-color: #2b579a; color: #000; }
.p3-theme-menu {
    position: absolute; top: 100%; left: 0; z-index: 1050;
    background: #fff; border: 1px solid #a6a6a6; border-radius: 3px;
    box-shadow: 0 4px 12px rgba(0,0,0,.2); padding: 8px; min-width: 340px;
}
.p3-theme-header { font-size: .72rem; font-weight: 700; color: #555; margin-bottom: 6px; padding: 0 2px; text-transform: uppercase; letter-spacing: .04em; }
.p3-theme-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 6px; padding: 2px 0; max-height: 260px; overflow-y: auto; }
.p3-theme-tile {
    background: transparent; border: 1px solid transparent; border-radius: 3px;
    padding: 4px 2px; cursor: pointer; display: flex; flex-direction: column; align-items: center; gap: 3px;
    font-size: .68rem; color: #444; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.p3-theme-tile:hover { background: #e7e6e6; border-color: #a6a6a6; color: #000; }
.p3-theme-tile.p3-theme-selected { border-color: #2b579a; background: #cce8ff; color: #000; font-weight: 600; }
.p3-theme-swatch { width: 44px; height: 28px; border-radius: 2px; border: 1px solid #c4c4c4; margin-bottom: 2px; }
.p3-theme-name { line-height: 1.1; }
.p3-theme-footer { border-top: 1px solid #e0e0e0; margin-top: 6px; padding-top: 6px; }
.p3-theme-link { display: block; font-size: .72rem; color: #2b579a; text-decoration: none; padding: 2px 2px; }
.p3-theme-link:hover { text-decoration: underline; background: #f2f2f2; }

.p3-swatch-facet { background: linear-gradient(135deg, #2b579a 0%, #e0e0e0 100%); }
.p3-swatch-gallery { background: linear-gradient(135deg, #c45a2e 0%, #f4f4f4 100%); }
.p3-swatch-integral { background: linear-gradient(135deg, #4472c4 0%, #ed7d31 100%); }
.p3-swatch-ion { background: linear-gradient(135deg, #7030a0 0%, #ffc000 100%); }
.p3-swatch-ionb { background: linear-gradient(135deg, #4472c4 0%, #2b579a 100%); }
.p3-swatch-organic { background: linear-gradient(135deg, #548235 0%, #bf9000 100%); }
.p3-swatch-retro { background: linear-gradient(135deg, #c00000 0%, #bfbfbf 100%); }
.p3-swatch-slice { background: linear-gradient(135deg, #2e75b6 0%, #70ad47 100%); }
.p3-swatch-wisp { background: linear-gradient(135deg, #4472c4 0%, #70ad47 100%); }
.p3-swatch-banded { background: linear-gradient(135deg, #4472c4 0%, #ffc000 100%); }
.p3-swatch-basis { background: linear-gradient(135deg, #2e75b6 0%, #a5a5a5 100%); }
.p3-swatch-berlin { background: linear-gradient(135deg, #c00000 0%, #f4b183 100%); }
.p3-swatch-circuit { background: linear-gradient(135deg, #2e75b6 0%, #bfbfbf 100%); }
.p3-swatch-damask { background: linear-gradient(135deg, #c00000 0%, #bfbfbf 100%); }
.p3-swatch-dividend { background: linear-gradient(135deg, #2e75b6 0%, #bf9000 100%); }
.p3-swatch-droplet { background: linear-gradient(135deg, #548235 0%, #bfbfbf 100%); }
.p3-swatch-frame { background: linear-gradient(135deg, #4472c4 0%, #bfbfbf 100%); }
.p3-swatch-gallery2 { background: linear-gradient(135deg, #c45a2e 0%, #bf9000 100%); }
.p3-swatch-main { background: linear-gradient(135deg, #c00000 0%, #ffc000 100%); }

.p3-word-color-group { display: flex; align-items: center; gap: 4px; }
.p3-word-ribbon-btn-color, .p3-word-ribbon-btn-font { position: relative; }
.p3-color-chip { width: 20px; height: 16px; border: 1px solid #a6a6a6; border-radius: 2px; display: inline-block; }

/* ── Dark mode Design tab ────────────────────────────────────────────── */
[data-theme="dark"] .p3-theme-menu { background: #2d2d2d; border-color: #3f3f3f; color: #ccc; }
[data-theme="dark"] .p3-theme-header { color: #aaa; }
[data-theme="dark"] .p3-theme-tile { color: #ccc; }
[data-theme="dark"] .p3-theme-tile:hover { background: #3f3f3f; color: #fff; border-color: #5a5a5a; }
[data-theme="dark"] .p3-theme-tile.p3-theme-selected { background: #004488; border-color: #3794ff; color: #fff; }
[data-theme="dark"] .p3-theme-swatch { border-color: #3f3f3f; }
[data-theme="dark"] .p3-theme-footer { border-top-color: #3f3f3f; }
[data-theme="dark"] .p3-theme-link { color: #3794ff; }
[data-theme="dark"] .p3-theme-link:hover { background: #3f3f3f; }
[data-theme="dark"] .p3-color-chip { border-color: #5a5a5a; }

/* ── Print ──────────────────────────────────────────────────────── */
@media print {
    .p3-word-titlebar, .p3-word-ribbon, .p3-word-statusbar, .p3-word-gutter { display: none !important; }
    .p3-word-shell { height: auto; overflow: visible; background: #fff; }
    .p3-word-editor { overflow: visible; display: block; }
    .p3-word-canvas { overflow: visible; background: #fff; padding: 0; }
    .p3-word-page { box-shadow: none; border: none; margin: 0; width: 100%; }
    .p3-word-page-body { padding: 1in; }
}
</style>

<script>
(function () {
    var saveUrl  = @json(route('documents.update-content', $document));
    var imageUrl = @json(route('documents.content-images', $document));
    var pdfUrl   = @json(route('documents.content-pdf', $document));
    var csrf     = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    var headerEl = document.getElementById('wordHeader');
    var bodyEl   = document.getElementById('wordBody');
    var footerEl = document.getElementById('wordFooter');
    var editors  = [headerEl, bodyEl, footerEl];
    var active   = bodyEl;

    var statusEl   = document.getElementById('saveStatus');   /* kept for API compat, unused in new UI */
    var wordEl     = document.getElementById('wordStatusWords');
    var zoomEl     = document.getElementById('wordStatusZoom');
    var zoomSlider = document.getElementById('wordZoomSlider');

    var dirty = false, saving = false, saveTimer = null;
    var savedRange = null;
    var zoom = 117;

    /* ── Placeholder & counts ──────────────────────────────────── */
    function isEmpty(el) {
        return el.textContent.trim() === '' && !el.querySelector('img,table,hr');
    }
    function refreshPlaceholder(el) {
        el.classList.toggle('is-empty', isEmpty(el));
    }
    function refreshCounts() {
        var text = bodyEl.innerText || '';
        var words = text.trim() === '' ? 0 : text.trim().split(/\s+/).length;
        wordEl.textContent = words + ' words';
    }

    editors.forEach(function (el) {
        if (el.innerHTML.trim() === '' || el.innerHTML.trim() === '<br>') el.innerHTML = '';
        el.addEventListener('focus', function () { active = el; });
        el.addEventListener('input', markDirty);
        refreshPlaceholder(el);
    });
    try { document.execCommand('styleWithCSS', false, true); } catch (e) {}
    refreshCounts();

    /* ── Dirty / save ─────────────────────────────────────────── */
    function setStatus(state, text) {
        /* kept minimal */
    }
    function markDirty() {
        dirty = true;
        scheduleSave();
        editors.forEach(refreshPlaceholder);
        refreshCounts();
    }
    function scheduleSave() {
        clearTimeout(saveTimer);
        saveTimer = setTimeout(function () { save({}); }, 2500);
    }
    function save(opts) {
        if (saving && !opts.force) return Promise.resolve();
        saving = true;
        var payload = {
            content: bodyEl.innerHTML,
            content_header: headerEl.innerHTML,
            content_footer: footerEl.innerHTML,
        };
        return fetch(saveUrl, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
            },
            body: JSON.stringify(payload),
            credentials: 'same-origin',
        }).then(function (res) {
            if (!res.ok) throw new Error('Save failed (' + res.status + ')');
            return res.json();
        }).then(function () {
            dirty = false;
            saving = false;
        }).catch(function () {
            saving = false;
            setTimeout(function () { save({ force: true }); }, 5000);
        });
    }

    /* ── Selection tracking ───────────────────────────────────── */
    document.addEventListener('selectionchange', function () {
        var sel = window.getSelection();
        if (!sel || !sel.rangeCount) return;
        var node = sel.anchorNode;
        var el = node && (node.nodeType === 1 ? node : node.parentElement);
        var host = el && el.closest ? el.closest('.p3-doc-region') : null;
        if (host) { active = host; savedRange = sel.getRangeAt(0).cloneRange(); }
    });

    function exec(cmd, value) {
        active.focus();
        if (savedRange) {
            var sel = window.getSelection();
            sel.removeAllRanges();
            sel.addRange(savedRange);
        }
        try { document.execCommand(cmd, false, value || null); } catch (e) {}
        markDirty();
    }

    /* ── Font controls ────────────────────────────────────────── */
    document.getElementById('fontFamily').addEventListener('change', function () {
        if (this.value) { try { document.execCommand('fontName', false, this.value); } catch (e) {} markDirty(); }
        this.value = '';
    });
    document.getElementById('fontSize').addEventListener('change', function () {
        if (!this.value) return;
        active.focus();
        if (savedRange) { var s = window.getSelection(); s.removeAllRanges(); s.addRange(savedRange); }
        var span = document.createElement('span');
        span.style.fontSize = this.value + 'pt';
        try {
            var r = window.getSelection().getRangeAt(0);
            r.surroundContents(span);
        } catch (e) {}
        markDirty();
        this.value = '';
    });
    document.getElementById('textColor').addEventListener('input', function () {
        try { document.execCommand('foreColor', false, this.value); } catch (e) {}
        markDirty();
    });
    document.getElementById('hlColor').addEventListener('input', function () {
        try { document.execCommand('backColor', false, this.value); } catch (e) {}
        markDirty();
    });

    /* ── Insert table / image ─────────────────────────────────── */
    document.getElementById('tableBtnWord').addEventListener('click', function () {
        var size = prompt('Table size — rows × columns (e.g. 3x4):', '3x4');
        if (!size) return;
        var m = /^(\d{1,2})\s*[x×]\s*(\d{1,2})$/.exec(size.trim());
        if (!m) { alert('Please enter a size like 3x4.'); return; }
        var rows = Math.min(parseInt(m[1], 10), 20);
        var cols = Math.min(parseInt(m[2], 10), 10);
        var html = '<table border="1" cellpadding="4" cellspacing="0"><thead><tr>';
        for (var c = 0; c < cols; c++) html += '<th>Column ' + (c + 1) + '</th>';
        html += '</tr></thead><tbody>';
        for (var r = 1; r < rows; r++) {
            html += '<tr>';
            for (var i = 0; i < cols; i++) html += '<td>&nbsp;</td>';
            html += '</tr>';
        }
        html += '</tbody></table><p><br></p>';
        exec('insertHTML', html);
    });
    var imageInputWord = document.getElementById('imageInputWord');
    document.getElementById('imageBtnWord').addEventListener('click', function () {
        imageInputWord.value = ''; imageInputWord.click();
    });
    imageInputWord.addEventListener('change', function () {
        var file = this.files && this.files[0];
        if (!file) return;
        if (!/^image\/(png|jpeg|gif|webp)$/.test(file.type)) { alert('PNG, JPEG, GIF or WebP only.'); return; }
        if (file.size > 5 * 1024 * 1024) { alert('Images must be 5 MB or smaller.'); return; }
        var fd = new FormData();
        fd.append('image', file);
        fetch(imageUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: fd,
            credentials: 'same-origin',
        }).then(function (res) { return res.json().then(function (d) { if (!res.ok) throw new Error(d.message); return d; }); })
          .then(function (data) {
              var alt = (file.name || 'image').replace(/["<>]/g, '');
              exec('insertHTML', '<img src="' + data.url + '" alt="' + alt + '" style="max-width:100%">');
          })
          .catch(function (err) { alert('Image upload failed: ' + err.message); });
    });

    /* ── Insert: Shapes / Icons / SmartArt / Chart / Screenshot ── */
    document.getElementById('shapesBtnWord').addEventListener('click', function () {
        alert('Shapes — draw a shape on the page (coming soon).');
    });
    document.getElementById('iconsBtnWord').addEventListener('click', function () {
        alert('Icons — insert an icon from the library (coming soon).');
    });
    document.getElementById('smartartBtnWord').addEventListener('click', function () {
        alert('SmartArt — insert a graphic (coming soon).');
    });
    document.getElementById('chartBtnWord').addEventListener('click', function () {
        alert('Chart — insert a chart (coming soon).');
    });
    document.getElementById('screenshotBtnWord').addEventListener('click', function () {
        alert('Screenshot — capture part of your screen (coming soon).');
    });

    /* ── Insert: Links ── */
    document.getElementById('linkBtnWord').addEventListener('click', function () {
        var url = prompt('Enter the link URL:', 'https://');
        if (!url) return;
        var text = window.getSelection().toString() || url;
        exec('insertHTML', '<a href="' + url + '" target="_blank" rel="noopener noreferrer">' + text + '</a>');
    });
    document.getElementById('bookmarkBtnWord').addEventListener('click', function () {
        var name = prompt('Bookmark name:', '');
        if (!name) return;
        exec('insertHTML', '<span style="border-bottom:1px dashed #00743F;" title="Bookmark: ' + name + '">' + name + '</span>');
    });
    document.getElementById('crossrefBtnWord').addEventListener('click', function () {
        alert('Cross-reference — link to a heading, figure or page (coming soon).');
    });

    /* ── Insert: Comments ── */
    document.getElementById('commentBtnWord').addEventListener('click', function () {
        alert('New Comment — add a comment to the selection (coming soon).');
    });

    /* ── Insert: Header & Footer ── */
    document.getElementById('headerBtnWord').addEventListener('click', function () {
        var h = document.getElementById('wordHeader');
        h.focus();
        h.setAttribute('data-placeholder', 'Type the header text here…');
        document.getElementById('wordHeaderBand').classList.remove('p3-band-hidden');
    });
    document.getElementById('footerBtnWord').addEventListener('click', function () {
        var f = document.getElementById('wordFooter');
        f.focus();
        f.setAttribute('data-placeholder', 'Type the footer text here…');
        document.getElementById('wordFooterBand').classList.remove('p3-band-hidden');
    });
    document.getElementById('pageNumBtnWord').addEventListener('click', function () {
        active.focus();
        exec('insertHTML', '<span class="p3-page-num">Page <span class="p3-page-field">1</span> of 1</span>');
    });

    /* ── Insert: Text ── */
    document.getElementById('textBoxBtnWord').addEventListener('click', function () {
        exec('insertHTML', '<div style="border:1px solid #00743F;padding:8px;max-width:200px">Type here</div>');
    });
    document.getElementById('quickPartsBtnWord').addEventListener('click', function () {
        alert('Quick Parts — insert reusable content blocks (coming soon).');
    });
    document.getElementById('wordArtBtnWord').addEventListener('click', function () {
        exec('insertHTML', '<span style="font-size:24px;color:#2b579a;font-weight:700">WordArt</span>');
    });
    document.getElementById('dropCapBtnWord').addEventListener('click', function () {
        alert('Drop Cap — enlarge first letter of a paragraph (coming soon).');
    });
    document.getElementById('dateTimeBtnWord').addEventListener('click', function () {
        var now = new Date();
        exec('insertHTML', now.toLocaleString());
    });
    document.getElementById('objectBtnWord').addEventListener('click', function () {
        alert('Object — insert an embedded object (coming soon).');
    });

    /* ── Insert: Symbols ── */
    document.getElementById('equationBtnWord').addEventListener('click', function () {
        exec('insertHTML', '<span style="font-family:Cambria Math">E=mc²</span>');
    });
    document.getElementById('symbolBtnWord').addEventListener('click', function () {
        exec('insertHTML', '<span style="font-family:Segoe UI Symbol">Ω</span>');
    });

    /* ── PDF export ───────────────────────────────────────────── */
    document.getElementById('shareBtn').addEventListener('click', function () {
        var url = window.location.href;
        navigator.clipboard.writeText(url).then(function () {
            var btn = document.getElementById('shareBtn');
            var old = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-check me-1"></i>Copied';
            setTimeout(function () { btn.innerHTML = old; }, 1200);
        });
    });

    /* ── Zoom ─────────────────────────────────────────────────── */
    var page = document.getElementById('wordPage');
    function applyZoom() {
        page.style.transform = 'scale(' + (zoom / 100) + ')';
        page.style.transformOrigin = 'top left';
        zoomEl.textContent = zoom + '%';
        zoomSlider.value = zoom;
    }
    document.getElementById('wordZoomSlider').addEventListener('input', function () {
        zoom = parseInt(this.value, 10); applyZoom();
    });
    var zoomBtns = document.querySelectorAll('.p3-status-right .p3-word-text-btn');
    zoomBtns[0].addEventListener('click', function () { zoom = Math.min(zoom + 10, 150); applyZoom(); });
    zoomBtns[1].addEventListener('click', function () { zoom = Math.max(zoom - 10, 60); applyZoom(); });
    applyZoom();

    /* ── Ribbon command buttons (Bold/Italic/Align/List/etc.) ───────── */
    document.getElementById('wordRibbon').addEventListener('click', function (e) {
        var btn = e.target.closest('[data-cmd]');
        if (!btn) return;
        e.preventDefault();
        exec(btn.getAttribute('data-cmd'));
    });

    /* ── Ribbon style buttons (Normal / Heading 1-3 / Title / Quote) ─ */
    document.getElementById('wordRibbon').addEventListener('click', function (e) {
        var btn = e.target.closest('[data-style]');
        if (!btn) return;
        e.preventDefault();
        var map = { p: '<p>', h1: '<h1>', h2: '<h2>', h3: '<h3>', title: '<h1>', subtitle: '<h2>', strong: '<strong>', em: '<em>', blockquote: '<blockquote>' };
        exec('formatBlock', map[btn.getAttribute('data-style')] || '<p>');
    });

    /* ── Ribbon ribbon tab switching ─────────────────────────────────── */
    document.querySelectorAll('.p3-word-ribbon-tab').forEach(function (tab) {
        tab.addEventListener('click', function () {
            document.querySelectorAll('.p3-word-ribbon-tab').forEach(function (t) {
                t.classList.remove('is-active'); t.setAttribute('aria-selected', 'false');
            });
            document.querySelectorAll('.p3-word-ribbon-content').forEach(function (c) { c.classList.add('d-none'); c.removeAttribute('aria-hidden'); });
            this.classList.add('is-active'); this.setAttribute('aria-selected', 'true');
            var target = document.getElementById('ribbon' + this.textContent.trim());
            if (target) { target.classList.remove('d-none'); target.setAttribute('aria-hidden', 'false'); }
        });
    });

    /* ── Design: Themes dropdown ─────────────────────────────────────── */
    var themesBtn   = document.getElementById('themesBtn');
    var themesMenu  = document.getElementById('themesMenu');
    var themePrev   = document.querySelector('.p3-theme-preview');
    var themeTiles  = document.querySelectorAll('.p3-theme-tile');

    themesBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        var open = !themesMenu.classList.contains('d-none');
        document.querySelectorAll('.p3-theme-menu').forEach(function (m) { m.classList.add('d-none'); });
        themesBtn.classList.toggle('is-active', !open);
        if (!open) themesMenu.classList.remove('d-none');
    });

    themeTiles.forEach(function (tile) {
        tile.addEventListener('click', function () {
            themeTiles.forEach(function (t) { t.classList.remove('p3-theme-selected'); });
            tile.classList.add('p3-theme-selected');
            var name = tile.getAttribute('title');
            themePrev.style.background = tile.querySelector('.p3-theme-swatch').style.background || '#fff';
            themesMenu.classList.add('d-none');
            themesBtn.classList.remove('is-active');
            markDirty();
        });
    });

    document.addEventListener('click', function (e) {
        if (!themesBtn.contains(e.target) && !themesMenu.contains(e.target)) {
            themesMenu.classList.add('d-none');
            themesBtn.classList.remove('is-active');
        }
    });

    /* ── Design: Colors / Fonts / Effects / Paragraph Spacing ────────── */
    document.getElementById('colorsBtn').addEventListener('click', function () {
        alert('Colors — pick a theme color scheme (coming soon).');
    });
    document.getElementById('fontsBtn').addEventListener('click', function () {
        alert('Fonts — pick a theme font pair (coming soon).');
    });
    document.getElementById('effectsBtn').addEventListener('click', function () {
        alert('Effects — pick theme line/fill effects (coming soon).');
    });
    document.getElementById('paraSpacingBtn').addEventListener('click', function () {
        alert('Paragraph Spacing — set theme paragraph spacing (coming soon).');
    });

    /* ── Design: Page Background ─────────────────────────────────────── */
    document.getElementById('watermarkBtn').addEventListener('click', function () {
        alert('Watermark — add a text or picture watermark (coming soon).');
    });
    document.getElementById('pageColorBtn').addEventListener('click', function () {
        alert('Page Color — choose page background color (coming soon).');
    });
    document.getElementById('pageBordersBtn').addEventListener('click', function () {
        alert('Page Borders — set page art/borders (coming soon).');
    });

    /* ── Find & replace modal ─────────────────────────────────── */
    document.getElementById('findBtn').addEventListener('click', function () {
        var m = new bootstrap.Modal(document.getElementById('findReplaceModalWord'));
        m.show();
    });
    document.getElementById('replaceBtnRibbon').addEventListener('click', function () {
        var m = new bootstrap.Modal(document.getElementById('findReplaceModalWord'));
        m.show();
    });
    function escapeRegex(s) { return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); }
    document.getElementById('findNextBtnWord').addEventListener('click', function () {
        var term = document.getElementById('findInputWord').value;
        if (!term) return;
        var found = false;
        try { found = window.find(term, false, false, false, false, false, false); } catch (e) {}
        document.getElementById('findStatusWord').textContent = found ? 'Found' : 'Not found';
    });
    document.getElementById('replaceBtnWord').addEventListener('click', function () {
        var term = document.getElementById('findInputWord').value;
        var repl = document.getElementById('replaceInputWord').value;
        if (!term) return;
        var sel = window.getSelection();
        if (sel && sel.toString().toLowerCase() === term.toLowerCase()) {
            try { document.execCommand('insertText', false, repl); } catch (e) {}
            markDirty();
            document.getElementById('findStatusWord').textContent = 'Replaced';
        } else {
            try { window.find(term); } catch (e) {}
            document.getElementById('findStatusWord').textContent = 'Select match first';
        }
    });
    document.getElementById('replaceAllBtnWord').addEventListener('click', function () {
        var term = document.getElementById('findInputWord').value;
        var repl = document.getElementById('replaceInputWord').value;
        if (!term) return;
        var mc = document.getElementById('matchCaseWord').checked;
        var re = new RegExp(escapeRegex(term), mc ? 'g' : 'gi');
        var changed = false;
        editors.forEach(function (root) {
            var before = root.innerHTML;
            root.innerHTML = root.innerHTML.replace(re, function () { changed = true; return repl; });
        });
        if (changed) { markDirty(); document.getElementById('findStatusWord').textContent = 'Replaced all'; }
        else { document.getElementById('findStatusWord').textContent = 'No matches'; }
    });

    /* ── Keyboard shortcuts ───────────────────────────────────── */
    document.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault(); save({ force: true });
        }
        if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
            e.preventDefault();
            var m = new bootstrap.Modal(document.getElementById('findReplaceModalWord'));
            m.show();
        }
    });

    /* ── Title bar: File / Sign in ────────────────────────────────── */
    document.querySelector('.p3-word-file-btn').addEventListener('click', function () {
        alert('File — Save, Open, Print, Export (coming soon).');
    });
    document.querySelector('.p3-word-actions .p3-word-text-btn:not(#shareBtn)').addEventListener('click', function () {
        alert('Sign in — enter your PANELCO III account (coming soon).');
    });

    /* ── Tell me searchbar ────────────────────────────────────────── */
    var tellMe = document.getElementById('tellMeInput');
    var tellMeBox = document.createElement('div');
    tellMeBox.className = 'p3-tellme-box d-none';
    tellMeBox.style.cssText = 'position:absolute;top:100%;left:0;right:0;z-index:1060;background:#fff;border:1px solid #a6a6a6;border-top:none;border-radius:0 0 3px 3px;box-shadow:0 4px 12px rgba(0,0,0,.15);max-height:260px;overflow-y:auto;font-size:.78rem;color:#333;';
    tellMe.parentNode.appendChild(tellMeBox);

    var tellCommands = [
        { label: 'Bold (Ctrl+B)', cmd: 'bold' },
        { label: 'Italic (Ctrl+I)', cmd: 'italic' },
        { label: 'Underline (Ctrl+U)', cmd: 'underline' },
        { label: 'Strikethrough', cmd: 'strikeThrough' },
        { label: 'Align Left (Ctrl+L)', cmd: 'justifyLeft' },
        { label: 'Center (Ctrl+E)', cmd: 'justifyCenter' },
        { label: 'Align Right (Ctrl+R)', cmd: 'justifyRight' },
        { label: 'Justify (Ctrl+J)', cmd: 'justifyFull' },
        { label: 'Bullets', cmd: 'insertUnorderedList' },
        { label: 'Numbering', cmd: 'insertOrderedList' },
        { label: 'Find (Ctrl+F)', cmd: 'find' },
        { label: 'Replace (Ctrl+H)', cmd: 'replace' },
        { label: 'Save (Ctrl+S)', cmd: 'save' },
        { label: 'Clear All Formatting', cmd: 'removeFormat' },
        { label: 'Text Color', cmd: 'foreColor' },
        { label: 'Highlight Color', cmd: 'backColor' },
    ];

    function tellRender(q) {
        tellMeBox.innerHTML = '';
        var shown = 0;
        tellCommands.forEach(function (c) {
            if (!q || c.label.toLowerCase().includes(q.toLowerCase())) {
                var row = document.createElement('div');
                row.className = 'p3-tellme-row';
                row.style.cssText = 'display:flex;align-items:center;padding:4px 8px;cursor:pointer;white-space:nowrap;';
                row.style.color = '#333';
                row.innerHTML = '<span style="flex:1">' + c.label + '</span><span class="small text-muted" style="white-space:nowrap">' + (c.cmd ? '⌘' : '') + '</span>';
                row.addEventListener('click', function (e) {
                    e.stopPropagation();
                    e.preventDefault();
                    tellMeBox.classList.add('d-none');
                    tellMe.value = '';
                    active.focus();
                    if (savedRange) { var s = window.getSelection(); s.removeAllRanges(); s.addRange(savedRange); }
                    if (c.cmd === 'save') { save({ force: true }); return; }
                    if (c.cmd === 'find' || c.cmd === 'replace') {
                        var m = new bootstrap.Modal(document.getElementById('findReplaceModalWord'));
                        m.show();
                        return;
                    }
                    try { document.execCommand(c.cmd, false, null); } catch (e) {}
                    markDirty();
                });
                tellMeBox.appendChild(row);
                shown++;
                if (shown >= 12) return false;
            }
        });
        tellMeBox.classList.toggle('d-none', shown === 0);
    }

    tellMe.addEventListener('input', function () { tellRender(this.value); });
    tellMe.addEventListener('focus', function () { tellRender(tellMe.value); });
    document.addEventListener('click', function (e) {
        if (!tellMe.contains(e.target) && !tellMeBox.contains(e.target)) tellMeBox.classList.add('d-none');
    });
    tellMe.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { tellMeBox.classList.add('d-none'); tellMe.value = ''; active.focus(); }
        if (e.key === 'Enter') {
            e.preventDefault();
            var first = tellMeBox.querySelector('.p3-tellme-row');
            if (first) { first.click(); }
            else { tellMeBox.classList.add('d-none'); }
        }
    });

    /* ── Home: Clipboard ──────────────────────────────────────────── */
    var home = document.getElementById('ribbonHome');
    home.querySelector('.p3-word-ribbon-btn-split').addEventListener('click', function () {
        navigator.clipboard.readText().then(function (text) {
            if (text) {
                active.focus();
                if (savedRange) { var s = window.getSelection(); s.removeAllRanges(); s.addRange(savedRange); }
                document.execCommand('insertText', false, text);
                markDirty();
            }
        }).catch(function () { alert('Paste — clipboard access blocked.'); });
    });
    home.querySelector('.p3-word-ribbon-btn-split-arrow').addEventListener('click', function (e) {
        e.stopPropagation();
        alert('Paste options — Keep Source Formatting / Merge Formatting / Keep Text Only (coming soon).');
    });
    home.querySelector('button[title="Cut (Ctrl+X)"]').addEventListener('click', function () { exec('cut'); });
    home.querySelector('button[title="Copy (Ctrl+C)"]').addEventListener('click', function () { exec('copy'); });
    home.querySelector('button[title="Format Painter"]').addEventListener('click', function () {
        alert('Format Painter — copy formatting from one selection to another (coming soon).');
    });

    /* ── Home: Font — Subscript / Superscript ─────────────────────── */
    home.querySelector('button[title="Subscript"]').addEventListener('click', function () { exec('subscript'); });
    home.querySelector('button[title="Superscript"]').addEventListener('click', function () { exec('superscript'); });

    /* ── Home: Paragraph — Show/Hide ¶ ────────────────────────────── */
    var showPara = false;
    var paraBtn = home.querySelector('button[title="Show/Hide ¶"]');
    paraBtn.addEventListener('click', function () {
        showPara = !showPara;
        document.querySelectorAll('.p3-doc-region').forEach(function (el) {
            el.classList.toggle('p3-show-marks', showPara);
        });
        paraBtn.classList.toggle('is-active', showPara);
    });

    /* ── Home: Paragraph — Sort ───────────────────────────────────── */
    home.querySelector('button[title="Sort"]').addEventListener('click', function (e) {
        e.preventDefault();
        alert('Sort — sort selected text / list items (coming soon).');
    });

    /* ── Home: Editing — Select ───────────────────────────────────── */
    document.getElementById('selectBtn').addEventListener('click', function () {
        active.focus();
        var sel = window.getSelection();
        var range = document.createRange();
        range.selectNodeContents(active);
        sel.removeAllRanges();
        sel.addRange(range);
        markDirty();
    });

    /* ── Warn on unload ───────────────────────────────────────── */
    window.addEventListener('beforeunload', function (e) {
        if (dirty) { e.preventDefault(); e.returnValue = ''; }
    });

})();
</script>
@endsection
