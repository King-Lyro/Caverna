import './bootstrap';
import '@toast-ui/editor/dist/toastui-editor.css';

const articleEditorElement = document.querySelector('[data-article-editor]');

if (articleEditorElement) {
	import('@toast-ui/editor').then(({ default: Editor }) => {
		const form = articleEditorElement.closest('[data-article-form]');
		const body = form.querySelector('textarea[name="body"]');
		const editor = new Editor({
			el: articleEditorElement,
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
			hooks: {
				addImageBlobHook: async (image, callback) => {
					const data = new FormData();
					data.append('image', image);

					try {
						const response = await fetch(articleEditorElement.dataset.uploadUrl, {
							method: 'POST',
							headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
							body: data,
						});
						if (!response.ok) throw new Error('Upload failed');

						const result = await response.json();
						callback(result.url, image.name || 'Article image');
					} catch {
						alert('Unable to upload the image. Use a JPG, PNG, GIF, or WebP under 5 MB.');
					}

					return false;
				},
			},
		});

		body.hidden = true;
		body.required = false;
		form.addEventListener('submit', () => { body.value = editor.getMarkdown(); });
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
