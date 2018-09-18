<?php
declare(strict_types=1);
/**
 * StreamObject class
 *
 * @package   YetiPDF\Objects\Basic
 *
 * @copyright YetiForce Sp. z o.o
 * @license   MIT
 * @author    Rafal Pospiech <r.pospiech@yetiforce.com>
 */

namespace YetiPDF\Objects\Basic;

/**
 * Class StreamObject
 */
class StreamObject extends \YetiPDF\Objects\PdfObject
{
	/**
	 * Basic object type (integer, string, boolean, dictionary etc..)
	 * @var string
	 */
	protected $basicType = 'Stream';
	/**
	 * Object name
	 * @var string
	 */
	protected $name = 'Stream';

	public function __construct(\YetiPDF\Document $document, bool $addToDocument = true)
	{
		$this->id = $document->getActualId();
		parent::__construct($document, $addToDocument);
	}

	/**
	 * {@inheritdoc}
	 */
	public function render(): string
	{
		return '';
	}
}
