<?php
declare(strict_types=1);
/**
 * BoxDimensions class
 *
 * @package   YetiForcePDF\Html
 *
 * @copyright YetiForce Sp. z o.o
 * @license   MIT
 * @author    Rafal Pospiech <r.pospiech@yetiforce.com>
 */

namespace YetiForcePDF\Html;

/**
 * Class BoxDimensions
 */
class Element extends \YetiForcePDF\Base
{
	/**
	 * @var Box
	 */
	protected $box;
	/**
	 * @var Box
	 */
	protected $parentBox;
	/**
	 * DOMElement tagName
	 * @var string
	 */
	protected $name;
	/**
	 * @var \YetiForcePDF\Document
	 */
	protected $document;
	/**
	 * @var \DOMElement
	 */
	protected $domElement;

	/**
	 * Initialisation
	 * @return $this
	 */
	public function init()
	{
		parent::init();
		$this->name = $this->domElement->tagName;
		return $this;
	}

	/**
	 * Set box for this element (element is always inside box)
	 * @param \YetiForcePDF\Html\Box $box
	 * @return $this
	 */
	public function setBox($box)
	{
		$this->box = $box;
		return $this;
	}

	/**
	 * Get box
	 * @return \YetiForcePDF\Html\Box
	 */
	public function getBox()
	{
		return $this->box;
	}

	/**
	 * Get parent element (from parent box)
	 * @return mixed
	 */
	public function getParent()
	{
		if ($this->box) {
			if ($parentBox = $this->box->getParent()) {
				return $parentBox->getElement();
			}
		}
	}

	/**
	 * Set dom element (only for parsing dom tree - domElement should not be used anywhere else)
	 * @param $element
	 * @return \YetiForcePDF\Html\Element
	 */
	public function setDOMElement($element): Element
	{
		$this->domElement = $element;
		return $this;
	}

	/**
	 * Get dom element
	 * @return \DOMElement
	 */
	public function getDOMElement()
	{
		return $this->domElement;
	}

	/**
	 * Get element internal unique id
	 * @return string
	 */
	public function getElementId(): string
	{
		return $this->elementId;
	}

	/**
	 * Parse element style
	 * @return \YetiForcePDF\Style\Style
	 */
	public function parseStyle(): \YetiForcePDF\Style\Style
	{
		$styleStr = null;
		if ($this->domElement instanceof \DOMElement && $this->domElement->hasAttribute('style')) {
			$styleStr = $this->domElement->getAttribute('style');
		}
		$style = (new \YetiForcePDF\Style\Style())
			->setDocument($this->document)
			->setElement($this)
			->setContent($styleStr);
		if ($this->box) {
			$style->setBox($this->box);
		}
		return $style->init();
	}

	/**
	 * Get element style
	 * @return \YetiForcePDF\Style\Style
	 */
	public function getStyle(): \YetiForcePDF\Style\Style
	{
		return $this->style;
	}

}
