<?php
declare(strict_types=1);
/**
 * Coordinates class
 *
 * @package   YetiForcePDF\Style\Coordinates
 *
 * @copyright YetiForce Sp. z o.o
 * @license   MIT
 * @author    Rafal Pospiech <r.pospiech@yetiforce.com>
 */

namespace YetiForcePDF\Style\Coordinates;

/**
 * Class Coordinates
 */
class Coordinates extends \YetiForcePDF\Base
{

	/**
	 * @var \YetiForcePDF\Style\Style
	 */
	protected $style;
	/**
	 * Absolute X position inside pdf coordinate system
	 * @var float
	 */
	protected $absolutePdfX = 0;
	/**
	 * Absolute Y position inside pdf coordinate system
	 * @var float
	 */
	protected $absolutePdfY = 0;
	/**
	 * Absolute X position inside html coordinate system
	 * @var float
	 */
	protected $absoluteHtmlX = 0;
	/**
	 * Absolute Y position inside html coordinate system
	 * @var float
	 */
	protected $absoluteHtmlY = 0;

	/**
	 * Set style
	 * @param \YetiForcePDF\Style\Style $style
	 * @return \YetiForcePDF\Style\Coordinates
	 */
	public function setStyle(\YetiForcePDF\Style\Style $style): Coordinates
	{
		$this->style = $style;
		return $this;
	}

	/**
	 * Get style
	 * @return \YetiForcePDF\Style\Style
	 */
	public function getStyle(): \YetiForcePDF\Style\Style
	{
		return $this->style;
	}

	/**
	 * Set absolute pdf coordinates x position
	 * @param float $x
	 * @return \YetiForcePDF\Style\Coordinates
	 */
	public function setAbsolutePdfX(float $x): Coordinates
	{
		$this->absolutePdfX = $x;
		return $this;
	}

	/**
	 * Set absolute pdf coordinates y position
	 * @param float $y
	 * @return \YetiForcePDF\Style\Coordinates
	 */
	public function setAbsolutePdfY(float $y): Coordinates
	{
		$this->absolutePdfY = $y;
		return $this;
	}

	/**
	 * Set absolute html coordinates x position
	 * @param float $x
	 * @return \YetiForcePDF\Style\Coordinates
	 */
	public function setAbsoluteHtmlX(float $x): Coordinates
	{
		$this->absoluteHtmlX = $x;
		return $this;
	}

	/**
	 * Set absolute html coordinates y position
	 * @param float $y
	 * @return \YetiForcePDF\Style\Coordinates
	 */
	public function setAbsoluteHtmlY(float $y): Coordinates
	{
		$this->absoluteHtmlY = $y;
		return $this;
	}

	/**
	 *GSet absolute pdf coordinates x position
	 * @param float $x
	 * @return \YetiForcePDF\Style\Coordinates
	 */
	public function getAbsolutePdfX(): float
	{
		return $this->absolutePdfX;
	}

	/**
	 * Get absolute pdf coordinates y position
	 * @param float $y
	 * @return \YetiForcePDF\Style\Coordinates
	 */
	public function getAbsolutePdfY(): float
	{
		return $this->absolutePdfY;
	}

	/**
	 * Get absolute html coordinates x position
	 * @param float $x
	 * @return \YetiForcePDF\Style\Coordinates
	 */
	public function getAbsoluteHtmlX(): float
	{
		return $this->absoluteHtmlX;
	}

	/**
	 * Get absolute html coordinates y position
	 * @param float $y
	 * @return \YetiForcePDF\Style\Coordinates
	 */
	public function getAbsoluteHtmlY(): float
	{
		return $this->absoluteHtmlY;
	}

	/**
	 * Convert html coordinates to pdf
	 */
	protected function convertHtmlToPdf()
	{
		$this->absolutePdfX = $this->absoluteHtmlX;
		$height = $this->style->getDimensions()->getHeight();
		//var_dump($this->style->getElement()->getDOMElement(), $height);
		$page = $this->document->getCurrentPage();
		$this->absolutePdfY = $page->getPageDimensions()->getHeight() - $this->absoluteHtmlY - $height;
	}


}
