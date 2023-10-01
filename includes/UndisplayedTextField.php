<?php

namespace MediaWiki\Extension\HoneyPot;

use HTMLTextField;

/**
 * Subclass of HTMLTextField that applies `display: none;` styles to the output
 */
class UndisplayedTextField extends HTMLTextField {

	/**
	 * Add a `style` attribute with `display: none;` to the field's attributes
	 *
	 * @inheritDoc
	 */
	public function getAttributes( array $list ) {
		$attribs = parent::getAttributes( $list );
		if ( isset( $attribs['style'] ) ) {
			// add a leading semicolon in case the existing styles are missing
			// it
			$attribs['style'] .= '; display: none;';
		} else {
			$attribs['style'] = 'display: none;';
		}
		return $attribs;
	}

	/**
	 * Remove the label from the field that isn't shown
	 *
	 * @inheritDoc
	 */
	public function getLabel() {
		return '';
	}
}
