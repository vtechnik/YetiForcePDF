<?php
declare(strict_types=1);
/**
 * BorderTopStyle class
 *
 * @package   YetiForcePDF\Style\Normalizer
 *
 * @copyright YetiForce Sp. z o.o
 * @license   MIT
 * @author    Rafal Pospiech <r.pospiech@yetiforce.com>
 */

namespace YetiForcePDF\Style\Normalizer;

/**
 * Class BorderTopStyle
 */
class BorderTopStyle extends Normalizer
{
	public function normalize($ruleValue): array
	{
		if (is_string($ruleValue)) {
			if (in_array($ruleValue, ['none', 'solid', 'dashed', 'dotted'])) {
				$style = $ruleValue;
			} else {
				$style = 'none';
			}
		} else {
			// if it is number - it was converted already
			$style = $ruleValue;
		}
		$normalized = [
			'border-top-style' => $style,
		];
		return $normalized;
	}
}
