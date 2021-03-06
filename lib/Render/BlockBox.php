<?php
declare(strict_types=1);
/**
 * BlockBox class
 *
 * @package   YetiForcePDF\Render
 *
 * @copyright YetiForce Sp. z o.o
 * @license   MIT
 * @author    Rafal Pospiech <r.pospiech@yetiforce.com>
 */

namespace YetiForcePDF\Render;

use \YetiForcePDF\Style\Style;
use \YetiForcePDF\Html\Element;
use \YetiForcePDF\Render\Coordinates\Coordinates;
use \YetiForcePDF\Render\Coordinates\Offset;
use \YetiForcePDF\Render\Dimensions\BoxDimensions;

/**
 * Class BlockBox
 */
class BlockBox extends Box
{

	/**
	 * @var Element
	 */
	protected $element;
	/**
	 * @var \YetiForcePDF\Render\LineBox
	 */
	protected $currentLineBox;

	/**
	 * {@inheritdoc}
	 */
	public function init()
	{
		parent::init();
		$this->dimensions = (new BoxDimensions())
			->setDocument($this->document)
			->setBox($this)
			->init();
		$this->coordinates = (new Coordinates())
			->setDocument($this->document)
			->setBox($this)
			->init();
		$this->offset = (new Offset())
			->setDocument($this->document)
			->setBox($this)
			->init();
		return $this;
	}

	/**
	 * Get element
	 * @return Element
	 */
	public function getElement()
	{
		return $this->element;
	}

	/**
	 * Set element
	 * @param Element $element
	 * @return $this
	 */
	public function setElement(Element $element)
	{
		$this->element = $element;
		$element->setBox($this);
		return $this;
	}

	/**
	 * Get new line box
	 * @return \YetiForcePDF\Render\LineBox
	 */
	public function getNewLineBox()
	{
		$this->currentLineBox = (new LineBox())->setDocument($this->document)->init();
		$this->appendChild($this->currentLineBox);
		$this->currentLineBox->getDimensions()->computeAvailableSpace();
		return $this->currentLineBox;
	}

	/**
	 * Close line box
	 * @param \YetiForcePDF\Render\LineBox|null $lineBox
	 * @param bool                              $createNew
	 * @return \YetiForcePDF\Render\LineBox
	 */
	public function closeLine()
	{
		$this->currentLineBox = null;
		return $this->currentLineBox;
	}

	/**
	 * Get current linebox
	 * @return \YetiForcePDF\Render\LineBox
	 */
	public function getCurrentLineBox()
	{
		return $this->currentLineBox;
	}

	/**
	 * Segregate
	 * @param $parentBlock
	 * @return $this
	 */
	public function buildTree($parentBlock = null)
	{
		$domElement = $this->getElement()->getDOMElement();
		if ($domElement->hasChildNodes()) {
			foreach ($domElement->childNodes as $childDomElement) {
				$element = (new Element())
					->setDocument($this->document)
					->setDOMElement($childDomElement)
					->init();
				$style = $element->parseStyle();
				if ($style->getRules('display') === 'block') {
					if ($this->getCurrentLineBox()) {
						$this->closeLine();
					}
					$box = (new BlockBox())
						->setDocument($this->document)
						->setElement($element)
						->setStyle($element->parseStyle())//second phase with css inheritance
						->init();
					$this->appendChild($box);
					$box->buildTree($box);
					continue;
				}
				// childDomElement is an inline element
				$box = (new InlineBox())
					->setDocument($this->document)
					->setElement($element)
					->setStyle($element->parseStyle())
					->init();
				if ($this->getCurrentLineBox()) {
					$currentLineBox = $this->getCurrentLineBox();
				} else {
					$currentLineBox = $this->getNewLineBox();
				}
				$currentLineBox->appendChild($box);
				if ($childDomElement instanceof \DOMText) {
					$box->setTextNode(true)->setText($childDomElement->textContent);
				} else {
					$box->buildTree($this);
				}
			}
		}
		return $this;
	}

	/**
	 * Measure width of this block
	 * @return $this
	 */
	public function measureWidth()
	{
		$dimensions = $this->getDimensions();
		$parent = $this->getParent();
		if ($parent) {
			if ($parent->getDimensions()->getWidth() !== null) {
				$dimensions->setWidth($parent->getDimensions()->getInnerWidth());
			} else {
				$maxWidth = 0;
				foreach ($this->getChildren() as $child) {
					$maxWidth = max($maxWidth, $child->getDimensions()->getOuterWidth());
				}
				$style = $this->getStyle();
				$maxWidth += $style->getHorizontalBordersWidth() + $style->getHorizontalPaddingsWidth();
				$dimensions->setWidth($maxWidth);
			}
		} else {
			$dimensions->setWidth($this->document->getCurrentPage()->getDimensions()->getWidth());
		}
		return $this;
	}

	/**
	 * Divide lines
	 * @return $this;
	 */
	public function divideLines()
	{
		foreach ($this->getChildren() as $child) {
			if ($child instanceof LineBox) {
				$lines = $child->divide();
				foreach ($lines as $line) {
					$this->insertBefore($line, $child);
					$line->reflow();
				}
				$this->removeChild($child);
			}
		}
		return $this;
	}

	/**
	 * Measure height
	 * @return $this
	 */
	public function measureHeight()
	{
		$height = 0;
		foreach ($this->getChildren() as $child) {
			$height += $child->getDimensions()->getOuterHeight();
		}
		$rules = $this->getStyle()->getRules();
		$height += $rules['border-top-width'] + $rules['padding-top'];
		$height += $rules['border-bottom-width'] + $rules['padding-bottom'];
		$this->getDimensions()->setHeight($height);
		return $this;
	}

	/**
	 * Offset elements
	 * @return $this
	 */
	public function offset()
	{
		$top = $this->document->getCurrentPage()->getCoordinates()->getY();
		$left = $this->document->getCurrentPage()->getCoordinates()->getX();
		if ($parent = $this->getParent()) {
			$parentRules = $parent->getStyle()->getRules();
			$top = $parentRules['padding-top'] + $parentRules['border-top-width'];
			$left = $parentRules['padding-left'] + $parentRules['border-left-width'];
			if (($previous = $this->getPrevious())) {
				$top = $previous->getOffset()->getTop() + $previous->getDimensions()->getHeight();
				$marginTop = $this->getStyle()->getRules('margin-top');
				if ($previous instanceof BlockBox) {
					$marginTop = max($marginTop, $previous->getStyle()->getRules('margin-bottom'));
				} elseif (!$previous instanceof LineBox) {
					$marginTop += $previous->getStyle()->getRules('margin-bottom');
				}
				$top += $marginTop;
			}
		}
		$this->getOffset()->setTop($top);
		$this->getOffset()->setLeft($left);
		return $this;
	}

	/**
	 * Position
	 * @return $this
	 */
	public function position()
	{
		$x = $this->document->getCurrentPage()->getCoordinates()->getX();
		$y = $this->document->getCurrentPage()->getCoordinates()->getY();
		if ($parent = $this->getParent()) {
			$x = $parent->getCoordinates()->getX() + $this->getOffset()->getLeft();
			$y = $parent->getCoordinates()->getY() + $this->getOffset()->getTop();
		}
		$this->getCoordinates()->setX($x);
		$this->getCoordinates()->setY($y);
		return $this;
	}

	/**
	 * Reflow
	 * @return $this
	 */
	public function reflow()
	{
		$parent = $this->getParent();
		if ($parent && !$parent->getDimensions()->getWidth()) {
			$this->getDimensions()->computeAvailableSpace();
			$this->offset();
			foreach ($this->getChildren() as $child) {
				$child->reflow();
			}
			$this->position();
			$this->divideLines();
			$this->measureWidth();
			$this->measureHeight();
		} else {
			$this->getDimensions()->computeAvailableSpace();
			$this->offset();
			$this->position();
			$this->measureWidth();
			foreach ($this->getChildren() as $child) {
				$child->reflow();
			}
			$this->divideLines();
			$this->measureHeight();
		}
		return $this;
	}

	/**
	 * Filter text
	 * Filter the text, this is applied to all text just before being inserted into the pdf document
	 * it escapes the various things that need to be escaped, and so on
	 *
	 * @return string
	 */
	protected function filterText($text)
	{
		$text = trim(preg_replace('/[\n\r\t\s]+/', ' ', mb_convert_encoding($text, 'UTF-8')));
		$text = preg_replace('/\s+/', ' ', $text);
		$text = mb_convert_encoding($text, 'UTF-16');
		return strtr($text, [')' => '\\)', '(' => '\\(', '\\' => '\\\\', chr(13) => '\r']);
	}

	/**
	 * Add border instructions
	 * @param array $element
	 * @param float $pdfX
	 * @param float $pdfY
	 * @param float $width
	 * @param float $height
	 * @return array
	 */
	protected function addBorderInstructions(array $element, float $pdfX, float $pdfY, float $width, float $height)
	{
		$rules = $this->style->getRules();
		$x1 = 0;
		$x2 = $width;
		$y1 = $height;
		$y2 = 0;
		$element[] = '% start border';
		if ($rules['border-top-width'] && $rules['border-top-style'] !== 'none') {
			$path = implode(" l\n", [
				implode(' ', [$x2, $y1]),
				implode(' ', [$x2 - $rules['border-right-width'], $y1 - $rules['border-top-width']]),
				implode(' ', [$x1 + $rules['border-left-width'], $y1 - $rules['border-top-width']]),
				implode(' ', [$x1, $y1])
			]);
			$borderTop = [
				'q',
				"{$rules['border-top-color'][0]} {$rules['border-top-color'][1]} {$rules['border-top-color'][2]} rg",
				"1 0 0 1 $pdfX $pdfY cm",
				"$x1 $y1 m", // move to start point
				$path . ' l h',
				'f',
				'Q'
			];
			$element = array_merge($element, $borderTop);
		}
		if ($rules['border-right-width'] && $rules['border-right-style'] !== 'none') {
			$path = implode(" l\n", [
				implode(' ', [$x2, $y2]),
				implode(' ', [$x2 - $rules['border-right-width'], $y2 + $rules['border-bottom-width']]),
				implode(' ', [$x2 - $rules['border-right-width'], $y1 - $rules['border-top-width']]),
				implode(' ', [$x2, $y1]),
			]);
			$borderTop = [
				'q',
				"1 0 0 1 $pdfX $pdfY cm",
				"{$rules['border-right-color'][0]} {$rules['border-right-color'][1]} {$rules['border-right-color'][2]} rg",
				"$x2 $y1 m",
				$path . ' l h',
				'f',
				'Q'
			];
			$element = array_merge($element, $borderTop);
		}
		if ($rules['border-bottom-width'] && $rules['border-bottom-style'] !== 'none') {
			$path = implode(" l\n", [
				implode(' ', [$x2, $y2]),
				implode(' ', [$x2 - $rules['border-right-width'], $y2 + $rules['border-bottom-width']]),
				implode(' ', [$x1 + $rules['border-left-width'], $y2 + $rules['border-bottom-width']]),
				implode(' ', [$x1, $y2]),
			]);
			$borderTop = [
				'q',
				"1 0 0 1 $pdfX $pdfY cm",
				"{$rules['border-bottom-color'][0]} {$rules['border-bottom-color'][1]} {$rules['border-bottom-color'][2]} rg",
				"$x1 $y2 m",
				$path . ' l h',
				'f',
				'Q'
			];
			$element = array_merge($element, $borderTop);
		}
		if ($rules['border-left-width'] && $rules['border-left-style'] !== 'none') {
			$path = implode(" l\n", [
				implode(' ', [$x1 + $rules['border-left-width'], $y1 - $rules['border-top-width']]),
				implode(' ', [$x1 + $rules['border-left-width'], $y2 + $rules['border-bottom-width']]),
				implode(' ', [$x1, $y2]),
				implode(' ', [$x1, $y1]),
			]);
			$borderTop = [
				'q',
				"1 0 0 1 $pdfX $pdfY cm",
				"{$rules['border-left-color'][0]} {$rules['border-left-color'][1]} {$rules['border-left-color'][2]} rg",
				"$x1 $y1 m",
				$path . ' l h',
				'f',
				'Q'
			];
			$element = array_merge($element, $borderTop);
		}
		$element[] = '% end border';
		return $element;
	}

	public function addBackgroundColorInstructions(array $element, float $pdfX, float $pdfY, float $width, float $height)
	{
		$rules = $this->style->getRules();
		if ($rules['background-color'] !== 'transparent') {
			$x1 = 0;
			$y1 = $height;
			$x2 = $width;
			$y2 = 0;
			$bgColor = [
				'q',
				"1 0 0 1 $pdfX $pdfY cm",
				"{$rules['background-color'][0]} {$rules['background-color'][1]} {$rules['background-color'][2]} rg",
				"0 0 $width $height re",
				'f',
				'Q'
			];
			$element = array_merge($element, $bgColor);
		}
		return $element;
	}

	/**
	 * Get element PDF instructions to use in content stream
	 * @return string
	 */
	public function getInstructions(): string
	{
		$style = $this->getStyle();
		$rules = $style->getRules();
		$font = $style->getFont();
		$fontStr = '/' . $font->getNumber() . ' ' . $font->getSize() . ' Tf';
		$coordinates = $this->getCoordinates();
		$pdfX = $coordinates->getPdfX();
		$pdfY = $coordinates->getPdfY();
		$htmlX = $coordinates->getX();
		$htmlY = $coordinates->getY();
		$dimensions = $this->getDimensions();
		$width = $dimensions->getWidth();
		$height = $dimensions->getHeight();
		$baseLine = $style->getFont()->getDescender();
		$baseLineY = $pdfY - $baseLine;
		if ($this->isTextNode()) {
			$textWidth = $style->getFont()->getTextWidth($this->getText());
			$textHeight = $style->getFont()->getTextHeight();
			$textContent = '(' . $this->filterText($this->getText()) . ')';
			$element = [
				'q',
				"1 0 0 1 $pdfX $baseLineY cm % html x:$htmlX y:$htmlY",
				"{$rules['color'][0]} {$rules['color'][1]} {$rules['color'][2]} rg",
				'BT',
				$fontStr,
				"$textContent Tj",
				'ET',
				'Q'
			];
			if ($this->drawTextOutline) {
				$element = array_merge($element, [
					'q',
					'1 w',
					'1 0 0 RG',
					"1 0 0 1 $pdfX $pdfY cm",
					"0 0 $textWidth $textHeight re",
					'S',
					'Q'
				]);
			}
		} else {
			$element = [];
			$element = $this->addBackgroundColorInstructions($element, $pdfX, $pdfY, $width, $height);
			$element = $this->addBorderInstructions($element, $pdfX, $pdfY, $width, $height);
		}
		return implode("\n", $element);
	}
}
