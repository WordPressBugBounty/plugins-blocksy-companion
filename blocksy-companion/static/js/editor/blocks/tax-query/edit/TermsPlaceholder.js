import { useState, createElement, useRef, useEffect } from '@wordpress/element'
import { useSelect, useDispatch } from '@wordpress/data'
import { __, sprintf } from 'ct-i18n'

import {
	createBlocksFromInnerBlocksTemplate,
	store as blocksStore,
} from '@wordpress/blocks'

import {
	useBlockProps,
	store as blockEditorStore,
	__experimentalBlockVariationPicker,
	__experimentalGetMatchingVariation as getMatchingVariation,
} from '@wordpress/block-editor'

const TermsPlaceholder = ({
	clientId,
	setAttributes,
	setIsPatternSelectionModalOpen,
}) => {
	const { replaceInnerBlocks } = useDispatch(blockEditorStore)

	return (
		<div className="components-placeholder is-large">
			<div className="components-placeholder__label">
				<svg
					viewBox="0 0 24 24"
					xmlns="http://www.w3.org/2000/svg"
					width="24"
					height="24"
					aria-hidden="true"
					focusable="false">
					<path
						d="M5.5 18v-1c0-.3.2-.5.5-.5h3c.3 0 .5.2.5.5v1c0 .3-.2.5-.5.5H6c-.3 0-.5-.2-.5-.5zm.5 2c-1.1 0-2-.9-2-2v-1c0-1.1.9-2 2-2h3c1.1 0 2 .9 2 2v1c0 1.1-.9 2-2 2H6zm-.5-9V6c0-.3.2-.5.5-.5h3c.3 0 .5.2.5.5v5c0 .3-.2.5-.5.5H6c-.3 0-.5-.2-.5-.5zm.5 2c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2h3c1.1 0 2 .9 2 2v5c0 1.1-.9 2-2 2H6zm8.5 0v5c0 .3.2.5.5.5h3c.3 0 .5-.2.5-.5v-5c0-.3-.2-.5-.5-.5h-3c-.3 0-.5.2-.5.5zM13 18c0 1.1.9 2 2 2h3c1.1 0 2-.9 2-2v-5c0-1.1-.9-2-2-2h-3c-1.1 0-2 .9-2 2v5zm1.5-11V6c0-.3.2-.5.5-.5h3c.3 0 .5.2.5.5v1c0 .3-.2.5-.5.5h-3c-.3 0-.5-.2-.5-.5zm.5 2c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2h3c1.1 0 2 .9 2 2v1c0 1.1-.9 2-2 2h-3z"
						fillRule="evenodd"
					/>
				</svg>

				{__('Advanced Taxonomies', 'blocksy-companion')}
			</div>
			<fieldset className="components-placeholder__fieldset">
				<legend className="components-placeholder__instructions">
					{__(
						'Start with a pattern or create a custom layout',
						'blocksy-companion'
					)}
				</legend>

				<button
					className="components-button is-primary"
					onClick={(e) => {
						e.preventDefault()

						setIsPatternSelectionModalOpen(true)
					}}>
					{__('Choose Pattern', 'blocksy-companion')}
				</button>

				<button
					className="components-button is-primary"
					onClick={(e) => {
						e.preventDefault()

						replaceInnerBlocks(
							clientId,
							createBlocksFromInnerBlocksTemplate([
								[
									'blocksy/tax-template',
									{},
									[
										[
											'blocksy/dynamic-data',
											{
												tagName: 'h2',
												field: 'wp:term_title',
												has_field_link: 'yes',
											},
										],

										[
											'blocksy/dynamic-data',
											{ field: 'wp:term_description' },
										],
									],
								],
							]),
							false
						)
					}}>
					{__('Create Custom Layout', 'blocksy-companion')}
				</button>
			</fieldset>
		</div>
	)
}

export default TermsPlaceholder
