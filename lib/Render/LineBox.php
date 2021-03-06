<?php
declare(strict_types=1);
/**
 * LineBox class
 *
 * @package   YetiForcePDF\Render
 *
 * @copyright YetiForce Sp. z o.o
 * @license   MIT
 * @author    Rafal Pospiech <r.pospiech@yetiforce.com>
 */

namespace YetiForcePDF\Render;

use YetiForcePDF\Style\Style;

/**
 * Class LineBox
 */
class LineBox extends Box
{
	/**
	 * @var Style
	 */
	protected $style;

	/**
	 * {@inheritdoc}
	 */
	public function init()
	{
		parent::init();
		$this->style = (new Style())
			->setDocument($this->document)
			->setBox($this)
			->init();
		return $this;
	}

	/**
	 * Will this box fit in line? (or need to create new one)
	 * @param \YetiForcePDF\Render\Box $box
	 * @return bool
	 */
	public function willFit(Box $box)
	{
		$childrenWidth = $this->getChildrenWidth();
		$availableSpace = $this->getDimensions()->getAvailableSpace();
		$boxOuterWidth = $box->getDimensions()->getOuterWidth();
		return bccomp((string)($availableSpace - $childrenWidth), (string)$boxOuterWidth) >= 0;
	}

	/**
	 * Are elements inside this line fit?
	 * @return bool
	 */
	public function elementsFit()
	{
		return $this->getDimensions()->getAvailableSpace() >= $this->getChildrenWidth();
	}

	/**
	 * Divide this line into more lines when objects doesn't fit
	 * @return LineBox[]
	 */
	public function divide()
	{
		if ($this->getParent()->getDimensions()->getWidth() !== null) {
			$lineWidth = $this->getParent()->getDimensions()->getInnerWidth();
		} else {
			$lineWidth = $this->getDimensions()->getAvailableSpace();
		}
		$lines = [];
		$this->clearStyles();
		$line = (new LineBox())->setDocument($this->document)->init();
		$line->getDimensions()->setWidth($lineWidth)->setAvailableSpace($lineWidth);
		$line->setParent($this->getParent());
		foreach ($this->getChildren() as $childBox) {
			if ($line->willFit($childBox)) {
				$line->appendChild($childBox);
			} else {
				$lines[] = $line;
				$line = (new LineBox())->setDocument($this->document)->init();
				$line->setParent($this->getParent());
				$line->getDimensions()->setWidth($lineWidth)->setUpAvailableSpace();
				$line->appendChild($childBox);
			}
		}
		// append last line
		$lines[] = $line;
		return $lines;

	}

	/**
	 * Measure width
	 * @return $this
	 */
	public function measureWidth()
	{
		$dimensions = $this->getDimensions();
		if ($this->getParent()->getDimensions()->getWidth() !== null) {
			$dimensions->setWidth($this->getParent()->getDimensions()->getInnerWidth());
		} else {
			$dimensions->setWidth($dimensions->getAvailableSpace());
		}
		return $this;
	}

	/**
	 * Measure height
	 * @return $this
	 */
	public function measureHeight()
	{
		$lineHeight = 0;
		foreach ($this->getChildren() as $child) {
			$lineHeight = max($lineHeight, $child->getStyle()->getRules('line-height'), $child->getDimensions()->getHeight());
		}
		$this->getDimensions()->setHeight($lineHeight);
		return $this;
	}

	/**
	 * Measure margins
	 * @return $this
	 */
	public function measureMargins()
	{
		$allChildren = [];
		$this->getAllChildren($allChildren);
		array_shift($allChildren);
		$marginTop = 0;
		$marginBottom = 0;
		foreach ($allChildren as $child) {
			if ($child->getStyle()->getRules('display') === 'inline-block') {
				$marginTop = max($marginTop, $child->getStyle()->getRules('margin-top'));
				$marginBottom = max($marginBottom, $child->getStyle()->getRules('margin-bottom'));
			}
		}
		$style = $this->getStyle();
		$style->setRule('margin-top', $marginTop);
		$style->setRule('margin-bottom', $marginBottom);
		return $this;
	}

	/**
	 * Position
	 * @return $this
	 */
	public function offset()
	{
		$parent = $this->getParent();
		$parentRules = $parent->getStyle()->getRules();
		$top = $parentRules['padding-top'] + $parentRules['border-top-width'];
		$left = $parentRules['padding-left'] + $parentRules['border-left-width'];
		if ($previous = $this->getPrevious()) {
			$top = $previous->getOffset()->getTop() + $previous->getDimensions()->getHeight() + $previous->getStyle()->getRules('margin-bottom');
		}
		$top += $this->getStyle()->getRules('margin-top');
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
		$parent = $this->getParent();
		$this->getCoordinates()->setX($parent->getCoordinates()->getX() + $this->getOffset()->getLeft());
		$this->getCoordinates()->setY($parent->getCoordinates()->getY() + $this->getOffset()->getTop());
		return $this;
	}

	/**
	 * Clear styles
	 * return $this;
	 */
	public function clearStyles()
	{
		$allNestedChildren = [];
		$maxLevel = 0;
		foreach ($this->getChildren() as $child) {
			$allChildren = [];
			$child->getAllChildren($allChildren);
			$maxLevel = max($maxLevel, count($allChildren));
			$allNestedChildren[] = $allChildren;
		}
		$clones = [];
		for ($row = 0; $row < $maxLevel; $row++) {
			foreach ($allNestedChildren as $column => $childArray) {
				if (isset($childArray[$row])) {
					$current = $childArray[$row];
					$clones[$current->getId()][] = $current;
				}
			}
		}
		foreach ($clones as $row => $cloneArray) {
			$count = count($cloneArray);
			if ($count > 1) {
				foreach ($cloneArray as $index => $clone) {
					if ($index === 0) {
						$clone->getStyle()->clearFirstInline();
					} elseif ($index === $count - 1) {
						$clone->getStyle()->clearLastInline();
					} elseif ($index > 0 && $index < ($count - 1)) {
						$clone->getStyle()->clearMiddleInline();
					}
				}
			}
		}
		return $this;
	}

	/**
	 * Reflow
	 * @return $this
	 */
	public function reflow()
	{
		$this->getDimensions()->computeAvailableSpace();
		$this->measureWidth();
		$this->measureHeight();
		$this->measureMargins();
		$this->offset();
		$this->position();
		$this->clearStyles();
		foreach ($this->getChildren() as $child) {
			$child->reflow();
		}
		return $this;
	}

	/**
	 * Get children width
	 * @return float|int
	 */
	public function getChildrenWidth()
	{
		$width = 0;
		foreach ($this->getChildren() as $childBox) {
			$width += $childBox->getDimensions()->getOuterWidth();
		}
		return $width;
	}

	/**
	 * Get children height
	 * @return float|int
	 */
	public function getChildrenHeight()
	{
		$height = 0;
		foreach ($this->getChildren() as $childBox) {
			$height += $childBox->getDimensions()->getOuterHeight();
		}
		return $height;
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
		$rules = [
			'font-family' => 'NotoSerif-Regular',
			'font-size' => 12,
			'font-weight' => 'normal',
			'margin-left' => 0,
			'margin-top' => 0,
			'margin-right' => 0,
			'margin-bottom' => 0,
			'padding-left' => 0,
			'padding-top' => 0,
			'padding-right' => 0,
			'padding-bottom' => 0,
			'border-left-width' => 1,
			'border-top-width' => 1,
			'border-right-width' => 1,
			'border-bottom-width' => 1,
			'border-left-color' => [1, 0, 0, 1],
			'border-top-color' => [1, 0, 0, 1],
			'border-right-color' => [1, 0, 0, 1],
			'border-bottom-color' => [1, 0, 0, 1],
			'border-left-style' => 'solid',
			'border-top-style' => 'solid',
			'border-right-style' => 'solid',
			'border-bottom-style' => 'solid',
			'box-sizing' => 'border-box',
			'display' => 'block',
			'width' => 'auto',
			'height' => 'auto',
			'overflow' => 'visible',
		];
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
				'F',
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
				'F',
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
				'F',
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
				'F',
				'Q'
			];
			$element = array_merge($element, $borderTop);
		}
		$element[] = '% end border';
		return $element;
	}

	/**
	 * Get element PDF instructions to use in content stream
	 * @return string
	 */
	public function getInstructions(): string
	{

		$coordinates = $this->getCoordinates();
		$pdfX = $coordinates->getPdfX();
		$pdfY = $coordinates->getPdfY();
		$dimensions = $this->getDimensions();
		$width = $dimensions->getWidth();
		$height = $dimensions->getHeight();
		$element = [];
		$element = $this->addBorderInstructions($element, $pdfX, $pdfY, $width, $height);

		return implode("\n", $element);
	}
}
