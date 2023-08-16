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

namespace PhpOffice\PhpWord\Writer\Word2007\Style;

use PhpOffice\PhpWord\Shared\XMLWriter;
use PhpOffice\PhpWord\Style;
use PhpOffice\PhpWord\Style\Paragraph as ParagraphStyle;
use PhpOffice\PhpWord\Writer\Word2007\Element\ParagraphAlignment;

/**
 * Paragraph style writer.
 *
 * @since 0.10.0
 */
class Paragraph extends AbstractStyle
{
    /**
     * Without w:pPr.
     *
     * @var bool
     */
    private $withoutPPR = false;

    /**
     * Is inline in element.
     *
     * @var bool
     */
    private $isInline = false;

    /**
     * Write style.
     */
    public function write(): void
    {
        $xmlWriter = $this->getXmlWriter();
        $styleId = $this->style->getStyleId();
        $isStyleName = $this->isInline && null !== $styleId && is_string($styleId);
        if ($isStyleName) {
            if (!$this->withoutPPR) {
                $xmlWriter->startElement('w:pPr');
            }
            $xmlWriter->startElement('w:pStyle');
            $xmlWriter->writeAttribute('w:val', $styleId);
            $xmlWriter->endElement();
            if (!$this->withoutPPR) {
                $xmlWriter->endElement();
            }
        } else {
            $this->writeStyle();
        }
    }

    /**
     * Write full style.
     */
    private function writeStyle(): void
    {
        $style = $this->getStyle();
        if (!$style instanceof ParagraphStyle) {
            return;
        }
        $xmlWriter = $this->getXmlWriter();
        $styles = $style->getStyleValues();

        if (!$this->withoutPPR) {
            $xmlWriter->startElement('w:pPr');
        }

        // Style name
        if ($this->isInline === true) {
            $xmlWriter->writeElementIf($styles['name'] !== null, 'w:pStyle', 'w:val', $styles['name']);
        }

        // Pagination
        $xmlWriter->writeElementIf($styles['pagination']['widowControl'] === false, 'w:widowControl', 'w:val', '0');
        $xmlWriter->writeElementIf($styles['pagination']['keepNext'] === true, 'w:keepNext', 'w:val', '1');
        $xmlWriter->writeElementIf($styles['pagination']['keepLines'] === true, 'w:keepLines', 'w:val', '1');
        $xmlWriter->writeElementIf($styles['pagination']['pageBreak'] === true, 'w:pageBreakBefore', 'w:val', '1');

        // Paragraph alignment
        if ('' !== $styles['alignment']) {
            $paragraphAlignment = new ParagraphAlignment($styles['alignment']);
            $xmlWriter->startElement($paragraphAlignment->getName());
            foreach ($paragraphAlignment->getAttributes() as $attributeName => $attributeValue) {
                $xmlWriter->writeAttribute($attributeName, $attributeValue);
            }
            $xmlWriter->endElement();
        }

        //Right to left
        $xmlWriter->writeElementIf($styles['bidi'] === true, 'w:bidi');

        //Paragraph contextualSpacing
        $xmlWriter->writeElementIf($styles['contextualSpacing'] === true, 'w:contextualSpacing');

        //Paragraph textAlignment
        $xmlWriter->writeElementIf($styles['textAlignment'] !== null, 'w:textAlignment', 'w:val', $styles['textAlignment']);

        // Hyphenation
        $xmlWriter->writeElementIf($styles['suppressAutoHyphens'] === true, 'w:suppressAutoHyphens');

        // Child style: alignment, indentation, spacing, and shading
        $this->writeChildStyle($xmlWriter, 'Indentation', $styles['indentation']);
        if (isset($styles['font']) && $styles['font']->checkIsParagraphStyle()) {
            $this->writeChildStyle($xmlWriter, 'Font', $styles['font']);
        }
        $this->writeChildStyle($xmlWriter, 'Spacing', $styles['spacing']);
        $this->writeChildStyle($xmlWriter, 'Shading', $styles['shading']);

        // Tabs
        $this->writeTabs($xmlWriter, $styles['tabs']);

        // Numbering
        $this->writeNumbering($xmlWriter, $styles['numbering']);

        // Border
        if ($style->hasBorder()) {
            $xmlWriter->startElement('w:pBdr');

            $styleWriter = new MarginBorder($xmlWriter);
            $styleWriter->setSizes($style->getBorderSize());
            $styleWriter->setStyles($style->getBorderStyle());
            $styleWriter->setColors($style->getBorderColor());
            $styleWriter->write();

            $xmlWriter->endElement();
        } else {
            //页眉
            $border = $style->getBorder();
            if ($border !== null) {
                $xmlWriter->startElement('w:pBdr');

                $borderBottom = $border->getBorderBottom();
                $xmlWriter->startElement('w:bottom');
                $xmlWriter->writeAttributeIf($borderBottom['style'] !== null, 'w:val', $borderBottom['style']);
                $xmlWriter->writeAttributeIf($borderBottom['color'] !== null, 'w:color', $borderBottom['color']);
                $xmlWriter->writeAttributeIf($borderBottom['size'] !== null, 'w:sz', $borderBottom['size']);
                $xmlWriter->writeAttributeIf($borderBottom['space'] !== null, 'w:space', $borderBottom['space']);
                $xmlWriter->endElement();

                $xmlWriter->endElement();
            }
        }

        if (!$this->withoutPPR) {
            $xmlWriter->endElement(); // w:pPr
        }

        if (isset($styles['bookmarkStart']) || isset($styles['bookmarkEnd'])) {
            if (strlen($styles['bookmarkStart']['id']) != 0 || strlen($styles['bookmarkStart']['name']) != 0 ) {
                $xmlWriter->startElement('w:bookmarkStart');
                $xmlWriter->writeAttributeIf($styles['bookmarkStart']['id'] !== null, 'w:id', $styles['bookmarkStart']['id']);
                $xmlWriter->writeAttributeIf($styles['bookmarkStart']['name'] !== null, 'w:name', $styles['bookmarkStart']['name']);
                $xmlWriter->endElement();
            }
        }

        if (strlen($styles['bookmarkEnd']['id'])!= 0) {
            $xmlWriter->startElement('w:bookmarkEnd');
            $xmlWriter->writeAttributeIf($styles['bookmarkEnd']['id']!== null, 'w:id', $styles['bookmarkEnd']['id']);
            $xmlWriter->endElement();
        }

    }

    /**
     * Write tabs.
     *
     * @param \PhpOffice\PhpWord\Style\Tab[] $tabs
     */
    private function writeTabs(XMLWriter $xmlWriter, $tabs): void
    {
        if (!empty($tabs)) {
            $xmlWriter->startElement('w:tabs');
            foreach ($tabs as $tab) {
                $styleWriter = new Tab($xmlWriter, $tab);
                $styleWriter->write();
            }
            $xmlWriter->endElement();
        }
    }

    /**
     * Write numbering.
     *
     * @param array $numbering
     */
    private function writeNumbering(XMLWriter $xmlWriter, $numbering): void
    {
        $numStyle = $numbering['style'];
        $numLevel = $numbering['level'];

        /** @var \PhpOffice\PhpWord\Style\Numbering $numbering */
        $numbering = Style::getStyle($numStyle);
        if ($numStyle !== null && $numbering !== null) {
            $xmlWriter->startElement('w:numPr');
            $xmlWriter->startElement('w:numId');
            $xmlWriter->writeAttribute('w:val', $numbering->getIndex());
            $xmlWriter->endElement(); // w:numId
            $xmlWriter->startElement('w:ilvl');
            $xmlWriter->writeAttribute('w:val', $numLevel);
            $xmlWriter->endElement(); // w:ilvl
            $xmlWriter->endElement(); // w:numPr

            $xmlWriter->startElement('w:outlineLvl');
            $xmlWriter->writeAttribute('w:val', $numLevel);
            $xmlWriter->endElement(); // w:outlineLvl
        }
    }

    /**
     * Set without w:pPr.
     *
     * @param bool $value
     */
    public function setWithoutPPR($value): void
    {
        $this->withoutPPR = $value;
    }

    /**
     * Set is inline.
     *
     * @param bool $value
     */
    public function setIsInline($value): void
    {
        $this->isInline = $value;
    }
}
