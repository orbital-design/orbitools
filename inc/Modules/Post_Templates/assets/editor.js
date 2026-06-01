/**
 * Post Templates — block editor modal.
 *
 * Generic two-step modal (title → pick a type) that fires when a new
 * post of a configured CPT is opened and still has the placeholder
 * paragraph. All labels + endpoint config come from the localized
 * `orbitoolsPostTemplates` object set by Post_Templates::enqueue_editor_script().
 */

;(function () {
	const { select, dispatch } = wp.data
	const { parse, createBlock } = wp.blocks
	const { Modal, MenuGroup, MenuItem, Button, TextControl } = wp.components
	const { createElement: el, useState, render, unmountComponentAtNode } = wp.element

	const cfg = window.orbitoolsPostTemplates
	if (!cfg) return

	const labelLower = cfg.label.toLowerCase()

	function hasPlaceholder() {
		const blocks = select('core/block-editor').getBlocks()
		const content = wp.blocks.serialize(blocks)
		return content.includes(cfg.placeholderClass)
	}

	function getAvailableTypes() {
		const allTerms = select('core').getEntityRecords('taxonomy', cfg.taxonomy, {
			per_page: -1,
		})

		if (!allTerms || !cfg.validTermIds) {
			return []
		}

		return allTerms.filter((term) => cfg.validTermIds.includes(term.id))
	}

	function loadTemplateAndSetTerm(termId, termSlug) {
		dispatch('core/editor').editPost({
			[cfg.taxonomy]: [termId],
		})

		const formData = new FormData()
		formData.append('action', 'orbitools_pt_get_template')
		formData.append('nonce', cfg.nonce)
		formData.append('term_slug', termSlug)
		formData.append('post_type', cfg.postType)

		return fetch(cfg.ajaxUrl, {
			method: 'POST',
			body: formData,
		})
			.then((response) => response.json())
			.then((data) => {
				if (data.success && data.data.content) {
					dispatch('core/block-editor').resetBlocks(parse(data.data.content))
					return true
				}
				return false
			})
	}

	function skipTemplate() {
		dispatch('core/block-editor').resetBlocks([
			createBlock('core/paragraph', { placeholder: 'Start writing...' }),
		])
	}

	function TypeSelectionModal({ types, onClose }) {
		const [step, setStep] = useState(1)
		const [postTitle, setPostTitle] = useState('')
		const [selectedType, setSelectedType] = useState(null)
		const [isLoading, setIsLoading] = useState(false)

		const handleTitleSubmit = () => {
			if (!postTitle.trim()) {
				dispatch('core/notices').createErrorNotice(`Please enter a title for your ${labelLower}.`, {
					type: 'snackbar',
					isDismissible: true,
				})
				return
			}
			setStep(2)
		}

		const handleTypeSelect = (termId, termSlug) => {
			setSelectedType(termId)
			setIsLoading(true)

			if (postTitle.trim()) {
				dispatch('core/editor').editPost({ title: postTitle.trim() })
			}

			loadTemplateAndSetTerm(termId, termSlug)
				.then((success) => {
					if (success) {
						onClose()
					} else {
						setIsLoading(false)
						setSelectedType(null)
						dispatch('core/notices').createErrorNotice('Failed to load template. Please try again.', {
							type: 'snackbar',
							isDismissible: true,
						})
					}
				})
				.catch((error) => {
					console.error('Error loading template:', error)
					setIsLoading(false)
					setSelectedType(null)
					dispatch('core/notices').createErrorNotice('An error occurred while loading the template. Please try again.', {
						type: 'snackbar',
						isDismissible: true,
					})
				})
		}

		const handleSkip = () => {
			if (postTitle.trim()) {
				dispatch('core/editor').editPost({ title: postTitle.trim() })
			}
			skipTemplate()
			onClose()
		}

		const handleBack = () => {
			setStep(1)
			setSelectedType(null)
		}

		if (step === 1) {
			return el(
				Modal,
				{
					title: `Create New ${cfg.label}`,
					onRequestClose: null,
					isDismissible: false,
					className: 'orbitools-post-template-modal',
					style: { maxWidth: '500px' },
				},
				[
					el(
						'p',
						{
							key: 'description',
							style: { marginBottom: '16px', color: '#757575' },
						},
						`Enter a title for your ${labelLower}. You'll choose a template in the next step.`,
					),
					el(TextControl, {
						key: 'title-input',
						label: `${cfg.label} Title`,
						value: postTitle,
						onChange: (value) => setPostTitle(value),
						autoFocus: true,
						__next40pxDefaultSize: true,
						__nextHasNoMarginBottom: true,
						onKeyDown: (e) => {
							if (e.key === 'Enter') {
								e.preventDefault()
								handleTitleSubmit()
							}
						},
					}),
					el(
						'div',
						{
							key: 'footer',
							style: {
								marginTop: '24px',
								display: 'flex',
								justifyContent: 'space-between',
								alignItems: 'center',
							},
						},
						[
							el(
								Button,
								{
									key: 'skip',
									variant: 'link',
									isDestructive: true,
									onClick: handleSkip,
								},
								'Skip and start blank',
							),
							el(
								Button,
								{
									key: 'next',
									variant: 'primary',
									onClick: handleTitleSubmit,
									disabled: !postTitle.trim(),
								},
								'Next: Choose Template',
							),
						],
					),
				],
			)
		}

		return el(
			Modal,
			{
				title: 'Choose Template',
				onRequestClose: null,
				isDismissible: false,
				className: 'orbitools-post-template-modal',
				style: { maxWidth: '500px' },
			},
			[
				el(
					'p',
					{
						key: 'description',
						style: { marginBottom: '16px', color: '#757575' },
					},
					`Select the type of ${labelLower} you want to create. This will load the appropriate template.`,
				),
				el(
					MenuGroup,
					{
						key: 'menu-group',
						label: 'Available Templates',
					},
					types.map((type) =>
						el(
							MenuItem,
							{
								key: type.id,
								icon: selectedType === type.id && isLoading ? 'update' : null,
								isSelected: selectedType === type.id,
								disabled: isLoading,
								onClick: () => handleTypeSelect(type.id, type.slug),
							},
							type.name,
						),
					),
				),
				el(
					'div',
					{
						key: 'footer',
						style: {
							marginTop: '16px',
							display: 'flex',
							justifyContent: 'space-between',
							alignItems: 'center',
						},
					},
					[
						el(
							Button,
							{
								key: 'back',
								variant: 'secondary',
								onClick: handleBack,
								disabled: isLoading,
							},
							'Back',
						),
						el(
							Button,
							{
								key: 'skip',
								variant: 'link',
								isDestructive: true,
								disabled: isLoading,
								onClick: handleSkip,
							},
							'Continue without template',
						),
					],
				),
			],
		)
	}

	function showTypeSelectionModal() {
		const types = getAvailableTypes()

		const mountPoint = document.createElement('div')
		mountPoint.id = 'orbitools-post-template-modal-root'
		document.body.appendChild(mountPoint)

		const handleClose = () => {
			unmountComponentAtNode(mountPoint)
			document.body.removeChild(mountPoint)
		}

		render(el(TypeSelectionModal, { types, onClose: handleClose }), mountPoint)
	}

	function init() {
		let checkReadyInterval = null
		let checkTypesInterval = null

		checkReadyInterval = setInterval(() => {
			if (!select('core/editor') || !select('core')) {
				return
			}

			clearInterval(checkReadyInterval)
			checkReadyInterval = null

			if (!hasPlaceholder()) {
				return
			}

			let attempts = 0
			const maxAttempts = 20

			checkTypesInterval = setInterval(() => {
				attempts++
				const types = getAvailableTypes()

				if (types && types.length > 0) {
					clearInterval(checkTypesInterval)
					checkTypesInterval = null
					showTypeSelectionModal()
				} else if (attempts >= maxAttempts) {
					clearInterval(checkTypesInterval)
					checkTypesInterval = null
				}
			}, 500)
		}, 100)

		window.addEventListener('beforeunload', () => {
			if (checkReadyInterval) clearInterval(checkReadyInterval)
			if (checkTypesInterval) clearInterval(checkTypesInterval)
		})
	}

	wp.domReady(init)
})()
