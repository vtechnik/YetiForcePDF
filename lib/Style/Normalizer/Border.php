<?php
declare(strict_types=1);
/**
 * Border class
 *
 * @package   YetiForcePDF\Style\Normalizer
 *
 * @copyright YetiForce Sp. z o.o
 * @license   MIT
 * @author    Rafal Pospiech <r.pospiech@yetiforce.com>
 */

namespace YetiForcePDF\Style\Normalizer;

/**
 * Class Border
 */
class Border extends Normalizer
{
	public function normalize($ruleValue): array
	{
		$matches = [];
		preg_match('/([0-9]+)([a-z]+)\s+(solid|dashed|dotted|none)\s+(.+)/i', $ruleValue, $matches);
		$originalSize = (float)$matches[1];
		$originalUnit = $matches[2];
		if (isset($matches[3])) {
			$style = $matches[3];
		} else {
			$style = 'solid';
		}
		if (isset($matches[4])) {
			$color = $matches[4];
		} else {
			$color = '#000000';
		}
		$color = \YetiForcePDF\Style\Color::toRGBA($color, true);
		$normalized = [
			'border-width' => $this->style->convertUnits($originalUnit, $originalSize),
			'border-color' => $color,
			'border-style' => $style,
		];
		$normalizedAgain = [];
		foreach ($normalized as $normalizedName => $normalizedValue) {
			$normalizerName = \YetiForcePDF\Style\Normalizer\Normalizer::getNormalizerClassName($normalizedName);
			$normalizer = (new $normalizerName())->setDocument($this->document)->init();
			foreach ($normalizer->normalize($normalizedValue) as $name => $value) {
				$normalizedAgain[$name] = $value;
			}
		}
		return $normalizedAgain;
	}
}
