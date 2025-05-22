import { Fragment, createElement, useMemo, useRef } from '@wordpress/element'

import {
	useBlockProps,
	__experimentalUseBorderProps as useBorderProps,
} from '@wordpress/block-editor'

import classnames from 'classnames'
import { useSettings } from '@wordpress/block-editor'

import { __ } from 'ct-i18n'

import TitlePreview from './wp/TitlePreview'
import ExcerptPreview from './wp/ExcerptPreview'
import DatePreview from './wp/DatePreview'
import CommentsPreview from './wp/CommentsPreview'
import AuthorPreview from './wp/AuthorPreview'
import TaxonomyPreview from './wp/TaxonomyPreview'
import ImagePreview from './wp/ImagePreview'
import AvatarPreview from './wp/AvatarPreview'
import TermTextPreview from './wp/TermTextPreview'
import TermImagePreview from './wp/TermImagePreview'
import FeaturedImagePreview from './wp/FeaturedImagePreview'

import { useBlockSupportsCustom } from '../hooks/use-block-supports-custom'
import { useEffect } from 'react'

const TextField = ({
	fieldDescriptor,
	fieldsDescriptor,

	attributes,
	attributes: { align, tagName: TagName, before, after, fallback },
	postId,
	postType,

	termId,
	taxonomy,
}) => {
	const ref = useRef(null)

	const blockProps = useBlockProps({
		className: classnames('ct-dynamic-data', {
			[`has-text-align-${align}`]: align,
		}),
		ref,
	})

	const uniqueClass = blockProps.className
		.split(' ')
		.find((c) => c.startsWith('wp-elements-'))

	const previewData = useBlockSupportsCustom({
		fieldType: 'text',
		attributes,
		uniqueClass,
	})

	const borderProps = useBorderProps(attributes)

	useEffect(() => {
		if (ref.current) {
			const shadowNode = ref.current.querySelector(
				'.ct-dynamic-shadow-node'
			)
			const styleTag = ref.current.querySelector('style')

			const clonedStyle = styleTag?.cloneNode(true)
			const clonedShadowNode = shadowNode?.cloneNode(true)

			const nodesToRemove = Array.from(ref.current.childNodes).filter(
				(node) => node !== shadowNode
			)
			nodesToRemove.forEach((node) => {
				ref.current.removeChild(node)
			})

			const wrapperContainer = document.createElement('div')
			wrapperContainer.innerHTML =
				before + '<div id="inject-here"></div>' + after

			const injectTarget = wrapperContainer.querySelector('#inject-here')
			if (injectTarget && clonedShadowNode) {
				injectTarget.replaceWith(...clonedShadowNode.childNodes)
			}

			if (clonedStyle) ref.current.appendChild(clonedStyle)
			ref.current.append(...wrapperContainer.childNodes)

			// // Create containers for before and after
			// const beforeContainer = document.createElement('div')
			// beforeContainer.innerHTML = before

			// const afterContainer = document.createElement('div')
			// afterContainer.innerHTML = after

			// // Append in desired order
			// if (clonedStyle) ref.current.appendChild(clonedStyle)
			// ref.current.append(...beforeContainer.childNodes)
			// if (clonedShadowNode)
			// 	ref.current.append(...clonedShadowNode.childNodes)
			// ref.current.append(...afterContainer.childNodes)
		}
	}, [
		fieldDescriptor,
		fieldsDescriptor,

		attributes,
		postId,
		postType,

		termId,
		taxonomy,
	])

	let Component = null

	if (fieldDescriptor.id === 'archive_title') {
		Component = () => __('Archive Title', 'blocksy-companion')
	}

	if (fieldDescriptor.id === 'archive_description') {
		Component = () => __('Archive Description', 'blocksy-companion')
	}

	if (
		fieldDescriptor.id === 'term_title' ||
		fieldDescriptor.id === 'term_description' ||
		fieldDescriptor.id === 'term_count'
	) {
		Component = TermTextPreview
	}

	if (fieldDescriptor.id === 'title') {
		Component = TitlePreview
	}

	if (fieldDescriptor.id === 'excerpt') {
		Component = ExcerptPreview
	}

	if (fieldDescriptor.id === 'date') {
		Component = DatePreview
	}

	if (fieldDescriptor.id === 'comments') {
		Component = CommentsPreview
	}

	if (fieldDescriptor.id === 'terms') {
		Component = TaxonomyPreview
	}

	if (fieldDescriptor.id === 'author') {
		Component = AuthorPreview
	}

	let css = ''

	if (fieldsDescriptor && fieldsDescriptor.dynamic_styles) {
		css = fieldsDescriptor.dynamic_styles
	}

	if (previewData.css) {
		css += previewData.css
	}

	if (Component) {
		return (
			<Fragment>
				<TagName
					{...blockProps}
					{...borderProps}
					style={{
						...(blockProps.style || {}),
						...(borderProps.style || {}),

						...(previewData.style || {}),
					}}
					className={classnames(
						blockProps.className,
						borderProps.className,
						previewData.className
					)}>
					{css && <style>{css}</style>}

					<div
						className="ct-dynamic-shadow-node"
						style={{
							display: 'none',
						}}>
						<Component
							attributes={attributes}
							postId={postId}
							postType={postType}
							termId={termId}
							taxonomy={taxonomy}
							fallback={fallback}
							fieldsDescriptor={fieldsDescriptor}
						/>
					</div>
				</TagName>
			</Fragment>
		)
	}

	return null
}

const WpFieldPreview = (props) => {
	const { fieldDescriptor } = props

	if (fieldDescriptor.id === 'featured_image') {
		return <FeaturedImagePreview {...props} />
	}

	if (fieldDescriptor.id === 'author_avatar') {
		return <AvatarPreview {...props} />
	}

	if (fieldDescriptor.id === 'term_image') {
		return <TermImagePreview {...props} />
	}

	if (fieldDescriptor.id === 'archive_image') {
		return <ImagePreview {...props} />
	}

	return <TextField {...props} />
}

export default WpFieldPreview
