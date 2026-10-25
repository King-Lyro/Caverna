import './bootstrap';
import './chatbox';
import '@toast-ui/editor/dist/toastui-editor.css';

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

document.addEventListener('submit', async (event) => {
	const form = event.target;
	if (form instanceof HTMLFormElement && form.hasAttribute('data-chat-form')) return;
	if (!(form instanceof HTMLFormElement) || event.defaultPrevented || form.method.toLowerCase() === 'get' || form.target) return;
	const previewInNewTab = event.submitter?.hasAttribute('data-preview-new-tab');
	if (event.submitter?.formTarget && !previewInNewTab) return;
	if (new URL(form.action).origin !== location.origin) return;
	if (form.dataset.confirm && !window.confirm(form.dataset.confirm)) {
		event.preventDefault();
		return;
	}
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
	const previewTab = previewInNewTab ? window.open('about:blank', '_blank') : null;
	if (previewInNewTab && !previewTab) {
		showError(['Allow pop-ups for this site to open the theme preview in a new tab, then try again.']);
		delete form.dataset.submitting;
		enabledButtons.forEach((button) => { button.disabled = false; });
		return;
	}
	if (previewTab) previewTab.opener = null;
	let previewOpened = false;
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
			if (form.hasAttribute('data-close-preview')) {
				form.dispatchEvent(new CustomEvent('form:redirect', { bubbles: true }));
				window.close();
				setTimeout(() => { location.assign(result.redirect); }, 150);
			} else if (previewTab) {
				previewTab.location.assign(result.redirect);
				previewOpened = true;
				form.dispatchEvent(new CustomEvent('form:saved', { bubbles: true }));
			} else {
				form.dispatchEvent(new CustomEvent('form:redirect', { bubbles: true }));
				location.assign(result.redirect);
			}
		} else {
			showError(['The server did not provide a destination. Please check your submission before retrying.']);
		}
	} catch (error) {
		console.error('Form submission failed', error);
		showError(['Unable to reach the server. Your entries and selected files have been kept; please try again.']);
	} finally {
		if (previewTab && !previewOpened) previewTab.close();
		delete form.dataset.submitting;
		enabledButtons.forEach((button) => { button.disabled = false; });
	}
});

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

let richEditorModule;
const initializeRichEditors = (root) => {
	const richEditorElements = [...root.querySelectorAll('[data-article-editor], [data-rich-editor]')]
		.filter((element) => !element.dataset.editorInitialized && (!element.hasAttribute('data-lazy-editor') || (
			!element.closest('details:not([open])') && !element.closest('[hidden]')
			&& element.closest('[data-theme-field]')?.querySelector('[data-theme-mode]').value === 'custom'
		)));
	if (!richEditorElements.length) return;
	richEditorElements.forEach((element) => { element.dataset.editorInitialized = 'true'; });
	richEditorModule ??= import('@toast-ui/editor');
	richEditorModule.then(({ default: Editor }) => {
		richEditorElements.forEach((element) => {
			if (!element.isConnected) return;
			const form = element.closest('form');
			const body = element.dataset.editorTarget
				? form.elements.namedItem(element.dataset.editorTarget)
				: form.querySelector('textarea[name="body"]');
			if (!(body instanceof HTMLTextAreaElement)) return;
			const uploadUrl = element.dataset.uploadUrl;
			const editor = new Editor({
				el: element,
				height: element.dataset.editorHeight || '500px',
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
			element.themeEditor = editor;
			editor.on('change', () => {
				body.value = editor.getMarkdown();
				body.dispatchEvent(new Event('input', { bubbles: true }));
			});
			form.addEventListener('submit', () => { if (element.isConnected) body.value = editor.getMarkdown(); });
		});
	}).catch((error) => {
		console.error('Rich text editor could not be loaded', error);
		richEditorElements.forEach((element) => {
			delete element.dataset.editorInitialized;
			element.textContent = 'The visual editor could not be loaded. You can still edit Markdown in the text area.';
			element.setAttribute('role', 'alert');
		});
	});
};
initializeRichEditors(document);
document.querySelectorAll('[data-theme-workspace]').forEach((workspace) => {
	workspace.addEventListener('toggle', (event) => {
		if (event.target instanceof HTMLDetailsElement && event.target.open) initializeRichEditors(event.target);
	}, true);
	const search = workspace.querySelector('[data-theme-page-search]');
	workspace.querySelector('[data-page-search-label]').hidden = false;
	search.addEventListener('input', () => {
		const links = [...workspace.querySelectorAll('[data-theme-page-link]')];
		links.forEach((link) => { link.hidden = !link.textContent.toLowerCase().includes(search.value.trim().toLowerCase()); });
		workspace.querySelector('[data-no-pages]').hidden = links.some((link) => !link.hidden);
	});
	const trackedForm = workspace.querySelector('[data-track-theme-changes]');
	let dirty = false;
	const markDirty = () => {
		dirty = true;
		trackedForm.querySelector('[data-theme-save-status]').textContent = 'You have unsaved changes';
		trackedForm.querySelector('.theme-save-bar').classList.add('is-dirty');
	};
	trackedForm.addEventListener('input', markDirty);
	trackedForm.addEventListener('change', markDirty);
	trackedForm.addEventListener('theme:changed', markDirty);
	trackedForm.addEventListener('form:saved', () => {
		dirty = false;
		trackedForm.querySelector('[data-theme-save-status]').textContent = 'Changes saved. Preview opened in a new tab.';
		trackedForm.querySelector('.theme-save-bar').classList.remove('is-dirty');
		trackedForm.querySelector('[data-form-errors]')?.remove();
	});
	document.addEventListener('submit', (event) => {
		if (event.target.target === '_blank' || event.submitter?.formTarget === '_blank') return;
		if (dirty && event.target !== trackedForm && !window.confirm('You have unsaved changes. Leave this editor without saving them?')) event.preventDefault();
	}, true);
	document.addEventListener('form:redirect', () => { dirty = false; });
	window.addEventListener('beforeunload', (event) => {
		if (dirty) { event.preventDefault(); event.returnValue = ''; }
	});
});

document.querySelectorAll('[data-theme-schedule]').forEach((form) => {
	form.querySelectorAll('[data-annual-date]').forEach((input) => {
		const label = document.createElement('label');
		label.textContent = input.dataset.annualDate;
		const pair = document.createElement('span');
		pair.className = 'theme-date-pair';
		const month = document.createElement('select');
		const day = document.createElement('select');
		month.setAttribute('aria-label', `${input.dataset.annualDate} month`);
		day.setAttribute('aria-label', `${input.dataset.annualDate} day`);
		month.dataset.annualMonth = '';
		day.dataset.annualDay = '';
		month.add(new Option('Month', ''));
		day.add(new Option('Day', ''));
		for (let i = 1; i <= 12; i++) month.add(new Option(new Date(2000, i - 1, 1).toLocaleString('en', { month: 'long' }), String(i).padStart(2, '0')));
		const [savedMonth, savedDay] = input.value.split('-');
		month.value = savedMonth || '';
		const updateDays = () => {
			const previous = day.value;
			while (day.options.length > 1) day.remove(1);
			const days = month.value ? new Date(2000, Number(month.value), 0).getDate() : 31;
			for (let i = 1; i <= days; i++) day.add(new Option(String(i), String(i).padStart(2, '0')));
			day.value = previous && Number(previous) <= days ? previous : '';
		};
		updateDays();
		day.value = savedDay || '';
		const sync = () => { input.value = month.value && day.value ? `${month.value}-${day.value}` : ''; };
		month.addEventListener('change', () => { updateDays(); sync(); });
		day.addEventListener('change', sync);
		pair.append(month, day);
		label.append(pair);
		input.closest('label').hidden = true;
		input.type = 'hidden';
		input.closest('label').after(label);
	});
	const update = () => {
		form.querySelectorAll('[data-recurrence]').forEach((section) => {
			section.hidden = section.dataset.recurrence !== form.elements.namedItem('recurrence').value;
			section.querySelectorAll('input, select').forEach((input) => { input.disabled = section.hidden; input.required = !section.hidden; });
		});
	};
	form.elements.namedItem('recurrence').addEventListener('change', update);
	update();
});

const reindexThemeBlocks = (list) => {
	Array.from(list.children).forEach((block, index) => {
		block.querySelectorAll('[name]').forEach((input) => {
			input.name = `${list.dataset.prefix}[${index}]${input.name.slice(input.name.lastIndexOf('['))}`;
		});
		const editor = block.querySelector('[data-rich-editor]');
		if (editor) editor.dataset.editorTarget = block.querySelector('textarea').name;
	});
};

document.querySelectorAll('.theme-editor').forEach((form) => {
	let pendingUploads = 0;
	const saveButtons = [...form.querySelectorAll('button[type="submit"]')];
	form.addEventListener('submit', (event) => {
		if (pendingUploads) {
			event.preventDefault();
			window.alert('Wait for artwork uploads to finish before saving.');
		}
	});
	const changed = (section) => {
		const mode = section?.querySelector('[data-theme-mode]');
		if (mode) {
			const hidden = mode.value === 'hidden';
			section.querySelector('[data-field-hidden-message]').hidden = !hidden;
			section.querySelector('[data-field-source]').textContent = mode.value === 'inherit' ? section.dataset.defaultSource : (hidden ? 'Hidden' : 'Custom');
			section.querySelector('[data-field-reset]').disabled = mode.value === 'inherit';
			section.querySelector('[data-field-restore]').hidden = !hidden;
			const hide = section.querySelector('[data-field-hide]');
			if (hide) hide.hidden = hidden;
		}
	};
	const markCustom = (section) => {
		const mode = section?.querySelector('[data-theme-mode]');
		if (mode) mode.value = 'custom';
		if (section) changed(section);
		form.dispatchEvent(new Event('theme:changed'));
	};
	const previewImage = (section, url) => {
		const image = section.querySelector('[data-field-image-preview]');
		image.hidden = !url;
		if (url) image.src = url;
		else image.removeAttribute('src');
		section.querySelector('[data-field-image-empty]').hidden = !!url;
	};
	const resetField = (section) => {
		const type = section.dataset.fieldType;
		if (type === 'blocks') {
			const list = section.querySelector('[data-theme-block-list]');
			list.querySelectorAll('[data-rich-editor]').forEach((element) => element.themeEditor?.destroy());
			list.replaceChildren(section.querySelector('[data-default-blocks]').content.cloneNode(true));
			reindexThemeBlocks(list);
			section.querySelector('[data-block-empty]').hidden = !!list.children.length;
		} else {
			const value = section.querySelector('[name$="[value]"]');
			value.value = section.dataset.defaultValue;
			const editor = section.querySelector('[data-rich-editor]');
			editor?.themeEditor?.setMarkdown(value.value);
			if (type === 'image') {
				const file = section.querySelector('[data-field-image]');
				if (file.dataset.objectUrl) URL.revokeObjectURL(file.dataset.objectUrl);
				file.value = '';
				section.querySelector('[data-field-file-name]').textContent = 'JPG, PNG, GIF, or WebP · Up to 5 MB';
				const url = section.dataset.defaultImageUrl || '';
				previewImage(section, url);
			}
		}
		section.querySelector('[data-theme-mode]').value = 'inherit';
		changed(section);
		initializeRichEditors(section);
		form.dispatchEvent(new Event('theme:changed'));
	};
	form.querySelectorAll('[data-theme-field]').forEach((section) => {
		section.querySelector('[data-field-tools]').hidden = false;
		changed(section);
	});
	form.addEventListener('input', (event) => {
		const section = event.target.closest('.theme-field');
		const mode = section?.querySelector('select[name$="[mode]"]');
		if (mode && event.target !== mode && !event.target.matches('[data-artwork-file]')) markCustom(section);
		if (event.target.matches('[data-block-title-input]')) event.target.closest('[data-theme-block]').querySelector('[data-block-title]').textContent = event.target.value || 'Untitled card';
	});
	form.addEventListener('click', (event) => {
		const reset = event.target.closest('[data-field-reset]');
		if (reset) resetField(reset.closest('[data-theme-field]'));
		const hide = event.target.closest('[data-field-hide]');
		if (hide) {
			const section = hide.closest('[data-theme-field]');
			section.querySelector('[data-theme-mode]').value = 'hidden';
			changed(section);
			form.dispatchEvent(new Event('theme:changed'));
		}
		const restore = event.target.closest('[data-field-restore]');
		if (restore) {
			const section = restore.closest('[data-theme-field]');
			markCustom(section);
			initializeRichEditors(section);
		}
		const add = event.target.closest('[data-add-theme-block]');
		if (add) {
			const section = add.closest('.theme-field');
			const list = section.querySelector('[data-theme-block-list]');
			if (list.children.length >= 30) {
				window.alert('A region can contain at most 30 content boxes.');
				return;
			}
			const template = section.querySelector('template').content.cloneNode(true);
			list.append(template);
			reindexThemeBlocks(list);
			initializeRichEditors(list);
			section.querySelector('[data-block-empty]').hidden = true;
			markCustom(section);
			list.lastElementChild.querySelector('input').focus();
		}
		const move = event.target.closest('[data-move-block]');
		if (move) {
			const block = move.closest('[data-theme-block]');
			const list = block.parentElement;
			if (move.dataset.moveBlock === '-1' && block.previousElementSibling) list.insertBefore(block, block.previousElementSibling);
			if (move.dataset.moveBlock === '1' && block.nextElementSibling) list.insertBefore(block.nextElementSibling, block);
			reindexThemeBlocks(list);
			markCustom(block.closest('.theme-field'));
		}
		const clear = event.target.closest('[data-clear-artwork]');
		if (clear) {
			const upload = clear.closest('[data-theme-upload]');
			upload.querySelector('[data-artwork-path]').value = '';
			upload.querySelector('[data-artwork-preview]').hidden = true;
			markCustom(upload.closest('.theme-field'));
		}
	});
	form.addEventListener('change', async (event) => {
		if (event.target.matches('[data-block-remove]')) {
			const block = event.target.closest('[data-theme-block]');
			block.classList.toggle('is-removed', event.target.checked);
			block.querySelector('[data-block-summary]').textContent = event.target.checked ? 'Will be removed - uncheck to undo' : 'Edit card';
			markCustom(block.closest('.theme-field'));
		}
		if (event.target.matches('[data-theme-mode]')) {
			const section = event.target.closest('[data-theme-field]');
			if (event.target.value === 'inherit') resetField(section);
			else {
				changed(section);
				initializeRichEditors(section);
			}
		}
		if (event.target.matches('[data-field-image]') && event.target.files[0]) {
			const section = event.target.closest('[data-theme-field]');
			if (event.target.dataset.objectUrl) URL.revokeObjectURL(event.target.dataset.objectUrl);
			event.target.dataset.objectUrl = URL.createObjectURL(event.target.files[0]);
			previewImage(section, event.target.dataset.objectUrl);
			section.querySelector('[data-field-file-name]').textContent = `${event.target.files[0].name} - save to apply this image`;
			markCustom(section);
		}
		if (!event.target.matches('[data-artwork-file]') || !event.target.files[0]) return;
		const upload = event.target.closest('[data-theme-upload]');
		const status = upload.querySelector('[data-upload-status]');
		pendingUploads++;
		saveButtons.forEach((button) => { button.disabled = true; });
		const data = new FormData();
		data.append('image', event.target.files[0]);
		status.textContent = 'Uploading artwork…';
		try {
			const response = await fetch(upload.dataset.uploadUrl, {
				method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, Accept: 'application/json' }, body: data,
			});
			if (!response.headers.get('content-type')?.includes('application/json')) throw new Error('The upload returned an unexpected response.');
			const result = await response.json();
			if (!response.ok) throw new Error(Object.values(result.errors || {}).flat().join(' ') || result.message || 'Artwork upload failed.');
			upload.querySelector('[data-artwork-path]').value = result.path;
			const preview = upload.querySelector('[data-artwork-preview]');
			preview.src = result.url;
			preview.hidden = false;
			status.textContent = 'Artwork uploaded. Save this area to use it.';
			markCustom(upload.closest('.theme-field'));
		} catch (error) {
			console.error('Theme artwork upload failed', error);
			status.textContent = error.message || 'Artwork could not be uploaded. Please try again.';
		} finally {
			pendingUploads--;
			saveButtons.forEach((button) => { button.disabled = pendingUploads > 0; });
			event.target.value = '';
		}
	});
	document.querySelectorAll('[data-theme-colors]').forEach((form) => {
		const sample = form.querySelector('[data-color-sample]');
		const update = () => form.querySelectorAll('[data-color-key]').forEach((input) => sample.style.setProperty(`--sample-${input.dataset.colorKey}`, input.value));
		form.addEventListener('input', update);
		update();
	});
});

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
