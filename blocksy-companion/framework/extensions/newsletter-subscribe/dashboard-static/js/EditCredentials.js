import {
	createElement,
	useEffect,
	useState,
	Fragment,
} from '@wordpress/element'

import classnames from 'classnames'
import { __, sprintf } from 'ct-i18n'
import ListPicker from './ListPicker'
import { Select } from 'blocksy-options'

import useProExtensionInFree from '../../../../../static/js/dashboard/helpers/useProExtensionInFree'

const freeProviders = ['mailchimp', 'demo']

const EditCredentials = ({ extension, onCredentialsValidated }) => {
	const [provider, setProvider] = useState(extension.data.provider)

	const [apiKey, setApiKey] = useState(extension.data.api_key)
	const [apiUrl, setApiUrl] = useState(extension.data.api_url)
	const [listId, setListId] = useState(extension.data.list_id)
	const [isLoading, setIsLoading] = useState(false)

	const { isPro, showNotice, content } = useProExtensionInFree(extension, {
		strategy: 'pro',
	})

	useEffect(() => {
		if (
			extension.data &&
			!freeProviders.includes(extension.data.provider) &&
			!isPro
		) {
			setProvider(freeProviders[0])
		}
	}, [])

	const attemptToSaveCredentials = async () => {
		const body = new FormData()

		body.append('provider', provider)
		body.append('api_key', apiKey)
		body.append('api_url', apiUrl)
		body.append('list_id', listId)

		body.append(
			'action',
			'blocksy_ext_newsletter_subscribe_maybe_save_credentials'
		)

		body.append('nonce', ctDashboardLocalizations.dashboard_actions_nonce)

		setIsLoading(true)

		try {
			const response = await fetch(ctDashboardLocalizations.ajax_url, {
				method: 'POST',
				body,
			})

			if (response.status === 200) {
				const body = await response.json()

				if (body.success) {
					if (body.data.result !== 'api_key_invalid') {
						onCredentialsValidated()
					}
				}
			}
		} catch (e) {}

		await new Promise((r) => setTimeout(() => r(), 1000))

		setIsLoading(false)
	}

	return (
		<div
			className={classnames(
				'ct-extension-options ct-newsletter-subscribe-options'
			)}>
			<h4>{__('Connect Newsletter Provider', 'blocksy-companion')}</h4>

			<div
				className="ct-newsletter-credentials"
				data-columns={
					provider.indexOf('mailerlite') > -1 ||
					provider === 'activecampaign'
						? 4
						: ['mailpoet', 'fluentcrm'].includes(provider)
						? 2
						: 3
				}>
				<section>
					<label>{__('Provider', 'blocksy-companion')}</label>
					<Select
						onChange={(copy) => {
							console.log(copy, extension.data.providers)
							if (!isPro && !freeProviders.includes(copy)) {
								setProvider(copy)
								setTimeout(() => {
									setProvider(freeProviders[0])
								})
								showNotice()
								return
							}

							setProvider(copy)
						}}
						option={{
							placeholder: __(
								'Pick Mailing Service',
								'blocksy-companion'
							),
							choices: extension.data.providers,
						}}
						value={
							provider.indexOf('mailerlite') > -1
								? 'mailerlite-new'
								: provider
						}
					/>
				</section>

				{provider.indexOf('activecampaign') > -1 && (
					<section>
						<label>{__('API URL', 'blocksy-companion')}</label>

						<div className="ct-option-input">
							<input
								type="text"
								onChange={({ target: { value } }) =>
									setApiUrl(value)
								}
								value={apiUrl || ''}
							/>
						</div>
					</section>
				)}

				{provider.indexOf('mailerlite') > -1 && (
					<section>
						<label>{__('API Version', 'blocksy-companion')}</label>
						<Select
							onChange={(copy) => {
								setProvider(
									copy === 'new'
										? 'mailerlite-new'
										: 'mailerlite'
								)
							}}
							option={{
								placeholder: __(
									'Pick Mailing Service',
									'blocksy-companion'
								),
								choices: [
									{
										key: 'new',
										value: 'New',
									},

									{
										key: 'classic',
										value: 'Classic',
									},
								],
							}}
							value={
								provider === 'mailerlite-new'
									? 'new'
									: 'classic'
							}
						/>
					</section>
				)}

				{(freeProviders.includes(provider) ||
					ctDashboardLocalizations.plugin_data.is_pro) && (
					<Fragment>
						{!['mailpoet', 'fluentcrm'].includes(provider) ? (
							<section>
								<label>
									{__('API Key', 'blocksy-companion')}
								</label>

								<div className="ct-option-input">
									<input
										type="text"
										onChange={({ target: { value } }) =>
											setApiKey(value)
										}
										value={apiKey || ''}
									/>
								</div>
							</section>
						) : null}
						<section>
							<label>{__('List ID', 'blocksy-companion')}</label>

							<ListPicker
								listId={listId}
								onChange={(id) => setListId(id)}
								provider={provider}
								apiUrl={apiUrl}
								apiKey={apiKey}
							/>
						</section>
					</Fragment>
				)}
			</div>

			{provider === 'mailchimp' && (
				<span
					className="ct-option-description"
					dangerouslySetInnerHTML={{
						__html: sprintf(
							__(
								'More information on how to generate an API key for Mailchimp can be found %shere%s.',
								'blocksy-companion'
							),

							'<a target="_blank" href="https://mailchimp.com/help/about-api-keys/">',
							'</a>'
						),
					}}
				/>
			)}

			{ctDashboardLocalizations.plugin_data.is_pro &&
				provider.indexOf('mailerlite') > -1 && (
					<span
						className="ct-option-description"
						dangerouslySetInnerHTML={{
							__html: sprintf(
								__(
									'More information on how to generate an API key for Mailerlite can be found %shere%s. Please note that it is required at least one group to be created in your account for the integration to work. More info on how to create a group %shere%s.',
									'blocksy-companion'
								),

								'<a target="_blank" href="https://www.mailerlite.com/help/where-to-find-the-mailerlite-api-key-and-documentation">',
								'</a>',
								'<a target="_blank" href="https://www.mailerlite.com/help/how-to-create-and-use-groups">',
								'</a>'
							),
						}}
					/>
				)}

			{provider.indexOf('demo') > -1 && (
				<span
					className="ct-option-description"
					dangerouslySetInnerHTML={{
						__html: __(
							'This provider is used only for testing purposes. It doesnt register any real subscribers.',
							'blocksy-companion'
						),
					}}
				/>
			)}

			{ctDashboardLocalizations.plugin_data.is_pro &&
				provider === 'brevo' && (
					<span
						className="ct-option-description"
						dangerouslySetInnerHTML={{
							__html: sprintf(
								__(
									'More information on how to generate an API key for Brevo can be found %shere%s.',
									'blocksy-companion'
								),

								'<a target="_blank" href="https://help.brevo.com/hc/en-us/articles/209467485-Create-and-manage-your-API-keys">',
								'</a>'
							),
						}}
					/>
				)}

			{ctDashboardLocalizations.plugin_data.is_pro &&
				provider === 'convertkit' && (
					<span
						className="ct-option-description"
						dangerouslySetInnerHTML={{
							__html: sprintf(
								__(
									'More information on how to generate an API key for ConvertKit can be found %shere%s.',
									'blocksy-companion'
								),

								'<a target="_blank" href="https://developers.convertkit.com/#api-basics">',
								'</a>'
							),
						}}
					/>
				)}

			{ctDashboardLocalizations.plugin_data.is_pro &&
				provider === 'activecampaign' && (
					<span
						className="ct-option-description"
						dangerouslySetInnerHTML={{
							__html: sprintf(
								__(
									'More information on how to generate an API key for ActiveCampaign can be found %shere%s.',
									'blocksy-companion'
								),

								'<a target="_blank" href="https://developers.activecampaign.com/reference/overview">',
								'</a>'
							),
						}}
					/>
				)}

			{ctDashboardLocalizations.plugin_data.is_pro &&
				provider === 'campaignmonitor' && (
					<span
						className="ct-option-description"
						dangerouslySetInnerHTML={{
							__html: sprintf(
								__(
									'More information on how to generate an API key for Campaign Monitor can be found %shere%s.',
									'blocksy-companion'
								),

								'<a target="_blank" href="https://help.campaignmonitor.com/api-keys">',
								'</a>'
							),
						}}
					/>
				)}

			{ctDashboardLocalizations.plugin_data.is_pro &&
				provider === 'emailoctopus' && (
					<span
						className="ct-option-description"
						dangerouslySetInnerHTML={{
							__html: sprintf(
								__(
									'More information on how to generate an API key for EmailOctopus can be found %shere%s.',
									'blocksy-companion'
								),

								'<a target="_blank" href="https://emailoctopus.com/api-documentation/v2">',
								'</a>'
							),
						}}
					/>
				)}

			{ctDashboardLocalizations.plugin_data.is_pro &&
				provider === 'mailpoet' && (
					<span
						className="ct-option-description"
						dangerouslySetInnerHTML={{
							__html: sprintf(
								__(
									'More information on how to create a list in MailPoet can be found %shere%s.',
									'blocksy-companion'
								),

								'<a target="_blank" href="https://kb.mailpoet.com/article/282-create-a-list">',
								'</a>'
							),
						}}
					/>
				)}

			{ctDashboardLocalizations.plugin_data.is_pro &&
				provider === 'fluentcrm' && (
					<span
						className="ct-option-description"
						dangerouslySetInnerHTML={{
							__html: sprintf(
								__(
									'More information on how to create a list in fluentcrm can be found %shere%s.',
									'blocksy-companion'
								),

								'<a target="_blank" href="https://kb.fluentcrm.com/article/282-create-a-list">',
								'</a>'
							),
						}}
					/>
				)}

			<button
				className="ct-button-primary"
				disabled={
					(!apiKey &&
						!['mailpoet', 'fluentcrm'].includes(provider)) ||
					!listId ||
					isLoading ||
					(!apiUrl && provider === 'activecampaign')
				}
				onClick={() => attemptToSaveCredentials()}>
				{isLoading
					? __('Loading...', 'blocksy-companion')
					: __('Save Settings', 'blocksy-companion')}
			</button>

			{content}
		</div>
	)
}

export default EditCredentials
