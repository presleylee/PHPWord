<?php
/**
 * This file is part of PHPWord - A pure PHP library for reading and writing
 * word processing documents.
 *
 * PHPWord is free software distributed under the terms of the GNU Lesser
 * General Public License version 3 as published by the Free Software Foundation.
 *
 * For the full copyright and license information, please read the LICENSE
 * file that was distributed with this source code. For the full list of
 * contributors, visit https://github.com/PHPOffice/PHPWord/contributors.
 *
 * @see         https://github.com/PHPOffice/PHPWord
 *
 * @license     http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 */

namespace PhpOffice\PhpWord\Style;

use PhpOffice\PhpWord\Exception\InvalidStyleException;
use PhpOffice\PhpWord\Shared\Text;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\TextAlignment;

/**
 * Paragraph style.
 *
 * OOXML:
 * - General: alignment, outline level
 * - Indentation: left, right, firstline, hanging
 * - Spacing: before, after, line spacing
 * - Pagination: widow control, keep next, keep line, page break before
 * - Formatting exception: suppress line numbers, don't hyphenate
 * - Textbox options
 * - Tabs
 * - Shading
 * - Borders
 *
 * OpenOffice:
 * - Indents & spacing
 * - Alignment
 * - Text flow
 * - Outline & numbering
 * - Tabs
 * - Dropcaps
 * - Tabs
 * - Borders
 * - Background
 *
 * @see  http://www.schemacentral.com/sc/ooxml/t-w_CT_PPr.html
 */
class Paragraph extends Border
{
    /**
     * @const int One line height equals 240 twip
     */
    const LINE_HEIGHT = 240;

    /**
     * Aliases.
     *
     * @var array
     */
    protected $aliases = ['line-height' => 'lineHeight', 'line-spacing' => 'spacing'];

    /**
     * Parent style.
     *
     * @var string
     */
    private $basedOn = 'Normal';

    /**
     * Style for next paragraph.
     *
     * @var string
     */
    private $next;

    /**
     * @var string
     */
    private $alignment = '';

    /**
     * StyleId
     *
     * @var string
     */
    private $styleId = null;

    /**
     * Indentation.
     *
     * @var null|\PhpOffice\PhpWord\Style\Indentation
     */
    private $indentation;

    /**
     * Font
     *
     * @var null|\PhpOffice\PhpWord\Style\Font
     */
    private $font;

    /**
     * Font
     *
     * @var array
     */
    private $bookmark;

    /**
     * Spacing.
     *
     * @var \PhpOffice\PhpWord\Style\Spacing
     */
    private $spacing;

    /**
     * Text line height.
     *
     * @var float|int
     */
    private $lineHeight;

    /**
     * Allow first/last line to display on a separate page.
     *
     * @var bool
     */
    private $widowControl = true;

    /**
     * Keep paragraph with next paragraph.
     *
     * @var bool
     */
    private $keepNext = false;

    /**
     * Keep all lines on one page.
     *
     * @var bool
     */
    private $keepLines = false;

    /**
     * Start paragraph on next page.
     *
     * @var bool
     */
    private $pageBreakBefore = false;

    /**
     * Numbering style name.
     *
     * @var string
     */
    private $numStyle;

    /**
     * Numbering level.
     *
     * @var int
     */
    private $numLevel = 0;

    /**
     * Set of Custom Tab Stops.
     *
     * @var \PhpOffice\PhpWord\Style\Tab[]
     */
    private $tabs = [];

    /**
     * Shading.
     *
     * @var \PhpOffice\PhpWord\Style\Shading
     */
    private $shading;

    /**
     * Ignore Spacing Above and Below When Using Identical Styles.
     *
     * @var bool
     */
    private $contextualSpacing = false;

    /**
     * Right to Left Paragraph Layout.
     *
     * @var bool
     */
    private $bidi = false;

    /**
     * Vertical Character Alignment on Line.
     *
     * @var string
     */
    private $textAlignment;

    /**
     * Paragraph border
     *
     * @var mixed
     */
    private $border;

    /**
     * Suppress hyphenation for paragraph.
     *
     * @var bool
     */
    private $suppressAutoHyphens = false;

    private $br_type = null;

    /**
     * Set Style value.
     *
     * @param string $key
     * @param mixed $value
     *
     * @return self
     */
    public function setStyleValue($key, $value)
    {
        $key = Text::removeUnderscorePrefix($key);

        switch ($key) {
            case 'left':
            case 'right':
            case 'hanging':
                $value = $value * 720;  // 720 twips is 0.5 inch　twips是一种衡量屏幕或打印设备上的相对物理尺寸
            break;
        }
        return parent::setStyleValue($key, $value);
    }

    /**
     * Get style values.
     *
     * An experiment to retrieve all style values in one function. This will
     * reduce function call and increase cohesion between functions. Should be
     * implemented in all styles.
     *
     * @ignoreScrutinizerPatch
     *
     * @return array
     */
    public function getStyleValues()
    {
        $styles = [
            'name' => $this->getStyleName(),
            'basedOn' => $this->getBasedOn(),
            'next' => $this->getNext(),
            'alignment' => $this->getAlignment(),
            'styleId' => $this->getStyleId(),
            'indentation' => $this->getIndentation(),
            'font' => $this->getFont(),
            'spacing' => $this->getSpace(),
            'bookmarkStart' => [
                'id' => $this->getBookmark('start', 'id'),
                'name' => $this->getBookmark('start', 'name'),
            ],
            'bookmarkEnd' => [
                'id' => $this->getBookmark('end', 'id'),
            ],
            'pagination' => [
                'widowControl' => $this->hasWidowControl(),
                'keepNext' => $this->isKeepNext(),
                'keepLines' => $this->isKeepLines(),
                'pageBreak' => $this->hasPageBreakBefore(),
            ],
            'numbering' => [
                'style' => $this->getNumStyle(),
                'level' => $this->getNumLevel(),
            ],
            'tabs' => $this->getTabs(),
            'shading' => $this->getShading(),
            'contextualSpacing' => $this->hasContextualSpacing(),
            'bidi' => $this->isBidi(),
            'textAlignment' => $this->getTextAlignment(),
            'suppressAutoHyphens' => $this->hasSuppressAutoHyphens(),
            'br_type' => $this->getBrType(),
        ];

        return $styles;
    }

    /**
     * @since 0.13.0
     *
     * @return string
     */
    public function getAlignment()
    {
        return $this->alignment;
    }

    /**
     * @since 0.13.0
     *
     * @param string $value
     *
     * @return self
     */
    public function setAlignment($value)
    {
        if (Jc::isValid($value)) {
            $this->alignment = $value;
        }

        return $this;
    }

    /**
     * @since 0.13.0
     *
     * @return string
     */
    public function getStyleId()
    {
        return $this->styleId;
    }

    /**
     * @since 0.13.0
     *
     * @param string $value
     *
     * @return self
     */
    public function setStyleId($value = null)
    {
        if ($value) $this->styleId = $value;

        return $this;
    }

    /**
     * Get parent style ID.
     *
     * @return string
     */
    public function getBasedOn()
    {
        return $this->basedOn;
    }

    /**
     * Set parent style ID.
     *
     * @param string $value
     *
     * @return self
     */
    public function setBasedOn($value = 'Normal')
    {
        $this->basedOn = $value;

        return $this;
    }

    /**
     * Get style for next paragraph.
     *
     * @return string
     */
    public function getNext()
    {
        return $this->next;
    }

    /**
     * Set style for next paragraph.
     *
     * @param string $value
     *
     * @return self
     */
    public function setNext($value = null)
    {
        $this->next = $value;

        return $this;
    }

    /**
     * Get shading.
     *
     * @return \PhpOffice\PhpWord\Style\Indentation
     */
    public function getIndentation()
    {
        return $this->indentation;
    }

    /**
     * Get shading.
     *
     * @return \PhpOffice\PhpWord\Style\Indentation
     */
    public function getFont(){
        return $this->font;
    }

    /**
     * Set shading.
     *
     * @param mixed $value
     *
     * @return self
     */
    public function setIndentation($value = null)
    {
        $this->setObjectVal($value, 'Indentation', $this->indentation);

        return $this;
    }

    /**
     * Get indentation.
     *
     * @return int
     */
    public function getIndent()
    {
        return $this->getChildStyleValue($this->indentation, 'left');
    }

    /**
     * Set border.
     *
     * @param mixed $value
     *
     * @return self
     */
    public function setBorder($value = null)
    {
        if ($value !== null) {
            $this->setObjectVal($value, 'border', $this->border);
        }

        return $this;
    }

    /**
     * Get border.
     *
     * @return int
     */
    public function getBorder()
    {
        return $this->border;
    }

    /**
     * Set indentation.
     *
     * @param int $value
     *
     * @return self
     */
    public function setLeft($value = null)
    {
        return $this->setIndentation(['left' => $value]);
    }

    /**
     * Set indentation.
     *
     * @param int $value
     *
     * @return self
     */
    public function setRight($value = null)
    {
        return $this->setIndentation(['right' => $value]);
    }

    /**
     * Set font.
     *
     * @param int $value
     *
     * @return self
     */
    public function setFont($value = null)
    {
        $value['isParagraphStyle'] = true;
        $this->setObjectVal($value, 'Font', $this->font);
        return $this;
    }


    /**
     * Set indentation.
     *
     * @param int $value
     *
     * @return self
     */
    public function setFirstLine($value = null)
    {
        return $this->setIndentation(['firstLine' => $value]);
    }

    /**
     * Get firstLine.
     *
     * @return \PhpOffice\PhpWord\Style\FirstLine
     *
     * @todo Rename to getSpacing in 1.0
     */
    public function getFirstLine()
    {
        return $this->getChildStyleValue($this->indentation, 'firstLine');
    }

    /**
     * Set indLeftChar.
     *
     * @param int $value
     *
     * @return self
     */
    public function setIndLeftChar($value = null)
    {
        return $this->setIndentation(['indLeftChar' => $value]);
    }

    /**
     * Get indLeftChar.
     *
     * @return \PhpOffice\PhpWord\Style\FirstLine
     *
     * @todo Rename to getSpacing in 1.0
     */
    public function getIndLeftChar()
    {
        return $this->getChildStyleValue($this->indentation, 'indLeftChar');
    }

    /**
     * Get spacing.
     *
     * @return \PhpOffice\PhpWord\Style\Spacing
     *
     * @todo Rename to getSpacing in 1.0
     */
    public function getSpace()
    {
        return $this->spacing;
    }

    public function setbookmarkStartID($value = NULL){
        $this->setBookmark('start', 'id', $value);
    }

    public function setBookmarkStartName($value = NULL){
        $this->setBookmark('start', 'name', $value);
    }

    public function setBookmarkEnd($value = NULL){
        $this->setBookmark('end', 'id', $value);
    }

    public function setBookmark($pre, $key, $val){
        $this->bookmark[$pre][$key] = $val;
    }

    /**
     * Get spacing.
     *
     * @return \PhpOffice\PhpWord\Style\Spacing
     *
     * @todo Rename to getSpacing in 1.0
     */
    public function getBookmarkStartID()
    {
        return $this->getBookmark('start', 'id');
    }

    public function getBookmarkStartName()
    {
        return $this->getBookmark('start', 'name');
    }

    public function getBookmarkEndID()
    {
        return $this->getBookmark('end', 'id');
    }

    public function getBookmark($pre, $key)
    {
        return $this->bookmark[$pre][$key]??'';
    }

    /**
     * Set firstLineChars.
     *
     * @param int $value
     *
     * @return self
     */
    public function setFirstLineChars($value = null)
    {
        return $this->setIndentation(['firstLineChars' => $value]);
    }
    /**
     * Get firstLineChars.
     *
     * @return \PhpOffice\PhpWord\Style\FirstLine
     *
     * @todo Rename to getSpacing in 1.0
     */
    public function getFirstLineChars()
    {
        return $this->getChildStyleValue($this->indentation, 'firstLineChars');
    }

    /**
     * Get hanging.
     *
     * @return int
     */
    public function getHanging()
    {
        return $this->getChildStyleValue($this->indentation, 'hanging');
    }

    /**
     * Set hanging.
     *
     * @param int $value
     *
     * @return self
     */
    public function setHanging($value = null)
    {
        return $this->setIndentation(['hanging' => $value]);
    }

    /**
     * Get hanging.
     *
     * @return int
     */
    public function getHangingChars()
    {
        return $this->getChildStyleValue($this->indentation, 'hangingChars');
    }

    /**
     * Set hanging.
     *
     * @param int $value
     *
     * @return self
     */
    public function setHangingChars($value = null)
    {
        return $this->setIndentation(['hangingChars' => $value]);
    }


    /**
     * Set spacing.
     *
     * @param mixed $value
     *
     * @return self
     *
     * @todo Rename to setSpacing in 1.0
     */
    public function setSpace($value = null)
    {
        $this->setObjectVal($value, 'Spacing', $this->spacing);

        return $this;
    }

    /**
     * Get space before paragraph.
     *
     * @return int
     */
    public function getSpaceBefore()
    {
        return $this->getChildStyleValue($this->spacing, 'before');
    }

    /**
     * Set space before paragraph.
     *
     * @param int $value
     *
     * @return self
     */
    public function setSpaceBefore($value = null)
    {
        return $this->setSpace(['before' => $value]);
    }

    /**
     * Get space after paragraph.
     *
     * @return int
     */
    public function getSpaceAfter()
    {
        return $this->getChildStyleValue($this->spacing, 'after');
    }

    /**
     * Set space after paragraph.
     *
     * @param int $value
     *
     * @return self
     */
    public function setSpaceAfter($value = null)
    {
        return $this->setSpace(['after' => $value]);
    }

    /**
     * Set space after paragraph.
     *
     * @param int $value
     *
     * @return self
     */
    public function setSpaceLine($value = null)
    {
        return $this->setSpace(['line' => $value]);
    }

    /**
     * Get space after paragraph.
     *
     * @return int
     */
    public function getSpaceLine()
    {
        return $this->getChildStyleValue($this->spacing, 'line');
    }

    /**
     * Set space after paragraph.
     *
     * @param int $value
     *
     * @return self
     */
    public function setSpaceLineRule($value = null)
    {
        return $this->setSpace(['lineRule' => $value]);
    }

    /**
     * Get space after paragraph.
     *
     * @return int
     */
    public function getSpaceLineRule()
    {
        return $this->getChildStyleValue($this->spacing, 'lineRule');
    }

    /**
     * Get spacing between lines.
     *
     * @return float|int
     */
    public function getSpacing()
    {
        return $this->getChildStyleValue($this->spacing, 'line');
    }

    /**
     * Set spacing between lines.
     *
     * @param float|int $value
     *
     * @return self
     */
    public function setSpacing($value = null)
    {
        return $this->setSpace(['line' => $value]);
    }

    /**
     * Get spacing line rule.
     *
     * @return string
     */
    public function getSpacingLineRule()
    {
        return $this->getChildStyleValue($this->spacing, 'lineRule');
    }

    /**
     * Set the spacing line rule.
     *
     * @param string $value Possible values are defined in LineSpacingRule
     *
     * @return \PhpOffice\PhpWord\Style\Paragraph
     */
    public function setSpacingLineRule($value)
    {
        return $this->setSpace(['lineRule' => $value]);
    }

    /**
     * Get line height.
     *
     * @return float|int
     */
    public function getLineHeight()
    {
        return $this->lineHeight;
    }

    /**
     * Set the line height.
     *
     * @param float|int|string $lineHeight
     *
     * @return self
     */
    public function setLineHeight($lineHeight)
    {
        if (is_string($lineHeight)) {
            $lineHeight = (float) (preg_replace('/[^0-9\.\,]/', '', $lineHeight));
        }

        if ((!is_int($lineHeight) && !is_float($lineHeight)) || !$lineHeight) {
            throw new InvalidStyleException('Line height must be a valid number');
        }

        $this->lineHeight = $lineHeight;
        $this->setSpacing(($lineHeight - 1) * self::LINE_HEIGHT);
        $this->setSpacingLineRule(\PhpOffice\PhpWord\SimpleType\LineSpacingRule::AUTO);

        return $this;
    }

    /**
     * Get allow first/last line to display on a separate page setting.
     *
     * @return bool
     */
    public function hasWidowControl()
    {
        return $this->widowControl;
    }

    /**
     * Set keep paragraph with next paragraph setting.
     *
     * @param bool $value
     *
     * @return self
     */
    public function setWidowControl($value = true)
    {
        $this->widowControl = $this->setBoolVal($value, $this->widowControl);

        return $this;
    }

    /**
     * Get keep paragraph with next paragraph setting.
     *
     * @return bool
     */
    public function isKeepNext()
    {
        return $this->keepNext;
    }

    /**
     * Set keep paragraph with next paragraph setting.
     *
     * @param bool $value
     *
     * @return self
     */
    public function setKeepNext($value = true)
    {
        $this->keepNext = $this->setBoolVal($value, $this->keepNext);

        return $this;
    }

    /**
     * Get keep all lines on one page setting.
     *
     * @return bool
     */
    public function isKeepLines()
    {
        return $this->keepLines;
    }

    /**
     * Set keep all lines on one page setting.
     *
     * @param bool $value
     *
     * @return self
     */
    public function setKeepLines($value = true)
    {
        $this->keepLines = $this->setBoolVal($value, $this->keepLines);

        return $this;
    }

    /**
     * Get start paragraph on next page setting.
     *
     * @return bool
     */
    public function hasPageBreakBefore()
    {
        return $this->pageBreakBefore;
    }

    /**
     * Set start paragraph on next page setting.
     *
     * @param bool $value
     *
     * @return self
     */
    public function setPageBreakBefore($value = true)
    {
        $this->pageBreakBefore = $this->setBoolVal($value, $this->pageBreakBefore);

        return $this;
    }

    /**
     * Get numbering style name.
     *
     * @return string
     */
    public function getNumStyle()
    {
        return $this->numStyle;
    }

    /**
     * Set numbering style name.
     *
     * @param string $value
     *
     * @return self
     */
    public function setNumStyle($value)
    {
        $this->numStyle = $value;

        return $this;
    }

    /**
     * Get numbering level.
     *
     * @return int
     */
    public function getNumLevel()
    {
        return $this->numLevel;
    }

    /**
     * Set numbering level.
     *
     * @param int $value
     *
     * @return self
     */
    public function setNumLevel($value = 0)
    {
        $this->numLevel = $this->setIntVal($value, $this->numLevel);

        return $this;
    }

    /**
     * Get tabs.
     *
     * @return \PhpOffice\PhpWord\Style\Tab[]
     */
    public function getTabs()
    {
        return $this->tabs;
    }

    /**
     * Set tabs.
     *
     * @param array $value
     *
     * @return self
     */
    public function setTabs($value = null)
    {
        if (is_array($value)) {
            $this->tabs = $value;
        }

        return $this;
    }

    /**
     * Get shading.
     *
     * @return \PhpOffice\PhpWord\Style\Shading
     */
    public function getShading()
    {
        return $this->shading;
    }

    /**
     * Set shading.
     *
     * @param mixed $value
     *
     * @return self
     */
    public function setShading($value = null)
    {
        $this->setObjectVal($value, 'Shading', $this->shading);

        return $this;
    }

    /**
     * Get contextualSpacing.
     *
     * @return bool
     */
    public function hasContextualSpacing()
    {
        return $this->contextualSpacing;
    }

    /**
     * Set contextualSpacing.
     *
     * @param bool $contextualSpacing
     *
     * @return self
     */
    public function setContextualSpacing($contextualSpacing)
    {
        $this->contextualSpacing = $contextualSpacing;

        return $this;
    }

    /**
     * Get bidirectional.
     *
     * @return bool
     */
    public function isBidi()
    {
        return $this->bidi;
    }

    /**
     * Set bidi.
     *
     * @param bool $bidi
     *            Set to true to write from right to left
     *
     * @return self
     */
    public function setBidi($bidi)
    {
        $this->bidi = $bidi;

        return $this;
    }

    /**
     * Get textAlignment.
     *
     * @return string
     */
    public function getTextAlignment()
    {
        return $this->textAlignment;
    }

    /**
     * Set textAlignment.
     *
     * @param string $textAlignment
     *
     * @return self
     */
    public function setTextAlignment($textAlignment)
    {
        TextAlignment::validate($textAlignment);
        $this->textAlignment = $textAlignment;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasSuppressAutoHyphens()
    {
        return $this->suppressAutoHyphens;
    }

    /**
     * @param bool $suppressAutoHyphens
     */
    public function setSuppressAutoHyphens($suppressAutoHyphens): void
    {
        $this->suppressAutoHyphens = (bool) $suppressAutoHyphens;
    }


    public function setBrType($style = NULL) {
        $this->br_type = $style;
    }

    public function getBrType()
    {
        return $this->br_type;
    }
}
