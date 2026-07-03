<script lang="ts">
	import { onMount } from 'svelte';
	import {
		Loader,
		TabNav,
		TabNavItem,
		toast,
		IconMessage,
		Button,
		CodeBlock
	} from '@hyvor/design/components';
	import { getEmailByUuid, getEmailContent } from '../../../lib/actions/emailActions';
	import type { Send, SendContent } from '../../../types';
	import SingleBox from '../../../@components/content/SingleBox.svelte';
	import IconCaretLeft from '@hyvor/icons/IconCaretLeft';
	import { consoleUrlProject } from '../../../lib/consoleUrl';
	import { page } from '$app/state';
	import Overview from './Overview.svelte';
	import Preview from './Preview.svelte';

	let send: Send | null = $state(null);
	let loading = $state(true);
	let error: string | null = $state(null);
	let activeTab: 'overview' | 'preview' | 'raw' = $state('overview');

	let content: SendContent | null = $state(null);
	let contentLoading = $state(false);
	let contentError: string | null = $state(null);

	function fetchSend() {
		const emailUuid = page.params.uuid ?? '';
		return getEmailByUuid(emailUuid);
	}

	function loadContent() {
		if (content !== null || contentLoading) return;

		contentLoading = true;
		contentError = null;

		getEmailContent(page.params.uuid ?? '')
			.then((result) => {
				content = result;
			})
			.catch((err: any) => {
				contentError = err.message || 'Failed to load email content';
			})
			.finally(() => {
				contentLoading = false;
			});
	}

	function selectTab(tab: 'overview' | 'preview' | 'raw') {
		activeTab = tab;
		if (tab === 'preview' || tab === 'raw') {
			loadContent();
		}
	}

	onMount(() => {
		fetchSend()
			.then((result) => {
				send = result;
			})
			.catch((err: any) => {
				error = err.message || 'Failed to load email';
				toast.error(error);
			})
			.finally(() => {
				loading = false;
			});
	});
</script>

<SingleBox>
	{#if loading}
		<Loader full />
	{:else if error}
		<IconMessage error message={error} />
	{:else if send}
		<div class="email-detail">
			<div class="header">
				<div class="header-left">
					<Button size="small" color="input" as="a" href={consoleUrlProject(`sends`)}>
						{#snippet start()}
							<IconCaretLeft size={12} />
						{/snippet}
						All Sends
					</Button>
				</div>
			</div>

			<div class="content">
				<div class="tabs">
					<TabNav>
						<TabNavItem name="overview" active={activeTab === 'overview'} onclick={() => selectTab('overview')}>Overview</TabNavItem>
						<TabNavItem name="preview" active={activeTab === 'preview'} onclick={() => selectTab('preview')}>Preview</TabNavItem>
						<TabNavItem name="raw" active={activeTab === 'raw'} onclick={() => selectTab('raw')}>Raw</TabNavItem>
					</TabNav>
				</div>

				{#if activeTab === 'overview'}
					<Overview {send} onSendUpdate={(updated) => send = updated} />
				{/if}

				{#if activeTab === 'preview'}
					{#if contentLoading}
						<Loader full />
					{:else if contentError}
						<IconMessage error message={contentError} />
					{:else if content}
						<Preview {content} />
					{/if}
				{/if}

				{#if activeTab === 'raw'}
					{#if contentLoading}
						<Loader full />
					{:else if contentError}
						<IconMessage error message={contentError} />
					{:else if content}
						<div class="raw-content">
							<div class="raw-content-note">
								This is the raw email content, including headers and body.
							</div>
							<CodeBlock code={content.raw} language={null} />
						</div>
					{/if}
				{/if}
			</div>
		</div>
	{/if}
</SingleBox>

<style>
	.email-detail {
		height: 100%;
		display: flex;
		flex-direction: column;
	}

	.header {
		display: flex;
		align-items: center;
		justify-content: space-between;
		padding: 15px 25px;
		border-bottom: 1px solid var(--border);
	}

	.header-left {
		display: flex;
		align-items: center;
		gap: 15px;
	}

	.content {
		flex: 1;
		overflow: auto;
	}

	.tabs {
		padding: 20px 25px;
	}

	.raw-content {
		flex: 1;
		overflow: auto;
		padding: 0px 25px;
	}
	.raw-content-note {
		margin-bottom: 10px;
		font-size: 14px;
		color: var(--text-light);
	}

	@media (max-width: 768px) {
		.email-detail {
			padding: 15px;
		}

		.header {
			flex-direction: column;
			align-items: flex-start;
			gap: 15px;
		}

		.header-left {
			flex-direction: column;
			align-items: flex-start;
			gap: 10px;
		}
	}
</style>
