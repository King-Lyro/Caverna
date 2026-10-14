import './bootstrap';
import '@toast-ui/editor/dist/toastui-editor.css';

const riverText = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT, {
	acceptNode: (node) => node.textContent.includes('RiverClan') && !node.parentElement.closest('script, style, textarea, option, .riverclan-text, [data-rich-editor], [data-article-editor]')
		? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_REJECT,
});
const riverNodes = [];
while (riverText.nextNode()) riverNodes.push(riverText.currentNode);
riverNodes.forEach((node) => {
	const fragment = document.createDocumentFragment();
	node.textContent.split('RiverClan').forEach((part, index) => {
		if (index) {
			const label = document.createElement('span');
			label.className = 'riverclan-text';
			label.textContent = 'RiverClan';
			fragment.append(label);
		}
		fragment.append(document.createTextNode(part));
	});
	node.replaceWith(fragment);
});
document.querySelectorAll('option').forEach((option) => {
	if (option.textContent.includes('RiverClan')) option.classList.add('riverclan-text');
});

const styleSelectedAllegiance = (select) => {
	select.classList.toggle('riverclan-text', Array.from(select.selectedOptions).some((option) => option.textContent.includes('RiverClan')));
};
const oldInputElement = document.getElementById('form-old-input');
if (oldInputElement) {
	const oldInput = JSON.parse(oldInputElement.textContent);
	document.querySelectorAll('input[name], select[name], textarea[name]').forEach((input) => {
		if (['password', 'file', 'hidden', 'submit', 'button'].includes(input.type)) return;
		const parts = input.name.replace(/\]/g, '').split('[').filter(Boolean);
		let value = oldInput;
		for (const part of parts) {
			if (value === null || typeof value !== 'object' || !Object.hasOwn(value, part)) return;
			value = value[part];
		}
		if (['checkbox', 'radio'].includes(input.type)) {
			input.checked = Array.isArray(value) ? value.map(String).includes(input.value) : String(value) === input.value;
		} else if (input instanceof HTMLSelectElement && input.multiple) {
			Array.from(input.options).forEach((option) => { option.selected = (value || []).map(String).includes(option.value); });
		} else {
			input.value = value ?? '';
		}
	});
}

document.querySelectorAll('select').forEach((select) => {
	styleSelectedAllegiance(select);
	select.addEventListener('change', () => { styleSelectedAllegiance(select); });
});

document.addEventListener('submit', async (event) => {
	const form = event.target;
	if (!(form instanceof HTMLFormElement) || event.defaultPrevented || form.method.toLowerCase() === 'get' || form.target) return;
	if (new URL(form.action).origin !== location.origin) return;
	event.preventDefault();
	if (form.dataset.submitting === 'true') return;
	form.dataset.submitting = 'true';
	const buttons = Array.from(form.elements).filter((input) => input.type === 'submit');
	const enabledButtons = buttons.filter((button) => !button.disabled);
	const data = new FormData(form);
	if (event.submitter?.name) data.append(event.submitter.name, event.submitter.value);
	enabledButtons.forEach((button) => { button.disabled = true; });
	const showError = (messages) => {
		Array.from(form.elements).filter((input) => input.type === 'password').forEach((input) => { input.value = ''; });
		let alert = form.querySelector('[data-form-errors]');
		if (!alert) {
			alert = document.createElement('div');
			alert.className = 'form-alert';
			alert.dataset.formErrors = '';
			alert.setAttribute('role', 'alert');
			alert.tabIndex = -1;
			form.prepend(alert);
		}
		alert.replaceChildren(...messages.map((message) => {
			const line = document.createElement('p');
			line.textContent = message;
			return line;
		}));
		alert.focus();
	};
	try {
		const response = await fetch(form.action, {
			method: form.method,
			headers: { Accept: 'application/json', 'X-Preserve-Form': '1' },
			body: data,
		});
		if (!response.headers.get('content-type')?.includes('application/json')) {
			showError(['The server returned an unexpected response. Your entries and selected files are still here; please try again.']);
			return;
		}
		const result = await response.json();
		if (result.csrf_token) {
			document.querySelectorAll('input[name="_token"]').forEach((input) => { input.value = result.csrf_token; });
			const meta = document.querySelector('meta[name="csrf-token"]');
			if (meta) meta.content = result.csrf_token;
		}
		if (!response.ok) {
			const errors = Object.values(result.errors || {}).flat();
			showError(errors.length ? errors : [result.message || 'Your submission could not be saved. Please try again.']);
			return;
		}
		if (result.redirect) {
			location.assign(result.redirect);
		} else {
			showError(['The server did not provide a destination. Please check your submission before retrying.']);
		}
	} catch (error) {
		console.error('Form submission failed', error);
		showError(['Unable to reach the server. Your entries and selected files have been kept; please try again.']);
	} finally {
		delete form.dataset.submitting;
		enabledButtons.forEach((button) => { button.disabled = false; });
	}
});

const richEditorElements = document.querySelectorAll('[data-article-editor], [data-rich-editor]');

document.querySelectorAll('[data-character-owner]').forEach((select) => {
	const display = select.closest('label').nextElementSibling;
	const link = display.querySelector('a');
	const update = () => {
		const option = select.selectedOptions[0];
		display.hidden = !option?.dataset.ownerUrl;
		if (!display.hidden) {
			link.href = option.dataset.ownerUrl;
			link.textContent = option.dataset.ownerName;
		}
	};
	select.addEventListener('change', update);
	update();
});

if (richEditorElements.length) {
	import('@toast-ui/editor').then(({ default: Editor }) => {
		richEditorElements.forEach((element) => {
			const form = element.closest('form');
			const body = element.dataset.editorTarget
				? form.elements.namedItem(element.dataset.editorTarget)
				: form.querySelector('textarea[name="body"]');
			if (!(body instanceof HTMLTextAreaElement)) return;
			const uploadUrl = element.dataset.uploadUrl;
			const editor = new Editor({
				el: element,
				height: '500px',
				initialEditType: 'wysiwyg',
				previewStyle: 'vertical',
				initialValue: body.value,
				usageStatistics: false,
				toolbarItems: [
					['heading', 'bold', 'italic', 'strike'],
					['hr', 'quote'],
					['ul', 'ol', 'task'],
					['table', 'image', 'link'],
					['code', 'codeblock'],
				],
				...(uploadUrl ? {
					hooks: {
						addImageBlobHook: async (image, callback) => {
							const data = new FormData();
							data.append('image', image);
							const response = await fetch(uploadUrl, {
								method: 'POST',
								headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
								body: data,
							});
							if (!response.ok) {
								alert('Unable to upload the image. Use a JPG, PNG, GIF, or WebP under 5 MB.');
								return false;
							}
							const result = await response.json();
							callback(result.url, image.name || 'Article image');
							return false;
						},
					},
				} : {}),
			});
			body.hidden = true;
			body.required = false;
			form.addEventListener('submit', () => { body.value = editor.getMarkdown(); });
		});
	});
}

const territoryImage = document.querySelector('img[usemap="#territory-map"]');

if (territoryImage) {
	const resizeMap = () => {
		if (!territoryImage.naturalWidth || !territoryImage.naturalHeight) return;

		const { width, height } = territoryImage.getBoundingClientRect();
		document.querySelectorAll('map[name="territory-map"] area').forEach((area) => {
			area.coords = area.dataset.coords.split(',').map((coord, index) =>
				Math.round(Number(coord) * (index % 2 ? height / territoryImage.naturalHeight : width / territoryImage.naturalWidth))).join(',');
		});
	};

	territoryImage.addEventListener('load', resizeMap);
	window.addEventListener('resize', resizeMap);
	new ResizeObserver(resizeMap).observe(territoryImage);
	resizeMap();
}

document.querySelectorAll('.thread-post').forEach((post) => {
	const display = post.querySelector('[data-post-display]');
	const actions = post.querySelector('.thread-post-actions');
	const editPanel = post.querySelector('[data-post-edit-panel]');
	const reportPanel = post.querySelector('[data-post-report-panel]');

	const closePanels = () => {
		display.hidden = false;
		if (actions) actions.hidden = false;
		if (editPanel) editPanel.hidden = true;
		if (reportPanel) reportPanel.hidden = true;
	};

	post.querySelector('[data-post-edit-open]')?.addEventListener('click', () => {
		display.hidden = true;
		if (reportPanel) reportPanel.hidden = true;
		editPanel.hidden = false;
		editPanel.querySelector('textarea').focus();
	});

	post.querySelector('[data-post-report-open]')?.addEventListener('click', () => {
		if (actions) actions.hidden = true;
		reportPanel.hidden = false;
		reportPanel.querySelector('textarea').focus();
	});

	post.querySelectorAll('[data-post-action-cancel]').forEach((button) => button.addEventListener('click', closePanels));
});
