const box = document.querySelector('[data-chatbox]');

if (box) {
	const labels = JSON.parse(box.querySelector('[data-chat-labels]').textContent);
	const content = box.querySelector('[data-chat-content]');
	const toggle = box.querySelector('[data-chat-toggle]');
	const list = box.querySelector('[data-chat-messages]');
	const status = box.querySelector('[data-chat-status]');
	const error = box.querySelector('[data-chat-error]');
	const viewerLabel = box.querySelector('[data-chat-viewer]');
	const form = box.querySelector('[data-chat-form]');
	const send = box.querySelector('[data-chat-send]');
	const refreshButton = box.querySelector('[data-chat-refresh]');
	let timer;
	let activeRequest = null;
	let posting = false;
	let viewer = null;
	let fresh = false;
	let stopped = false;
	let refreshPending = false;

	const showError = (message) => {
		error.textContent = message;
		error.hidden = !message;
	};
	const updateSend = () => { send.disabled = posting || Boolean(activeRequest) || !fresh || !viewer?.can_post; };
	const isActive = () => !stopped && !content.hidden && !document.hidden;
	const request = async (url, options = {}) => {
		const controller = new AbortController();
		const timeout = setTimeout(() => controller.abort(), 12000);
		try {
			const response = await fetch(url, {
				...options, signal: controller.signal, cache: 'no-store',
				headers: { Accept: 'application/json', ...options.headers },
			});
			if (response.status === 419 || response.status === 401) throw new Error(labels.session_expired);
			if (!response.headers.get('content-type')?.includes('application/json')) throw new Error(labels.unexpected);
			const result = await response.json();
			if (!response.ok) throw new Error(Object.values(result.errors || {}).flat().join(' ') || result.message || labels.unexpected);
			return result;
		} finally {
			clearTimeout(timeout);
		}
	};
	const render = (snapshot) => {
		if (!Array.isArray(snapshot.messages) || !snapshot.viewer) throw new Error(labels.unexpected);
		const scroll = list.scrollTop;
		const focusedMessage = list.contains(document.activeElement)
			? document.activeElement.closest('[data-message-id]')?.dataset.messageId : null;
		const anchor = scroll > 0 ? Array.from(list.children).find((row) => row.offsetTop - list.offsetTop >= scroll) : null;
		const anchorId = anchor?.dataset.messageId;
		const anchorOffset = anchor ? anchor.offsetTop - list.offsetTop - scroll : 0;
		const rows = snapshot.messages.map((message) => {
			const row = document.createElement('li');
			row.dataset.messageId = message.id;
			const heading = document.createElement('header');
			const author = document.createElement(message.profile_url ? 'a' : 'strong');
			author.textContent = message.name;
			if (message.profile_url) author.href = message.profile_url;
			const badge = document.createElement('small');
			badge.textContent = message.badge;
			const time = document.createElement('time');
			time.dateTime = message.created_at;
			const date = new Date(message.created_at);
			time.textContent = date.toLocaleString(undefined, { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
			time.title = date.toLocaleString();
			heading.append(author, badge, time);
			const body = document.createElement('p');
			body.className = 'chatbox-body';
			body.textContent = message.body;
			row.append(heading, body);
			return row;
		});
		if (!rows.length) {
			const empty = document.createElement('li');
			empty.textContent = labels.empty;
			rows.push(empty);
		}
		list.replaceChildren(...rows);
		const restoredAnchor = anchorId ? list.querySelector(`[data-message-id="${anchorId}"]`) : null;
		list.scrollTop = restoredAnchor ? restoredAnchor.offsetTop - list.offsetTop - anchorOffset : scroll;
		if (focusedMessage) {
			const focusedAuthor = list.querySelector(`[data-message-id="${focusedMessage}"] a`);
			(focusedAuthor || list).focus({ preventScroll: true });
		}
		viewer = snapshot.viewer;
		if (viewer.muted_until) {
			const date = new Date(viewer.muted_until);
			viewer.message = labels.muted_until.replace(':time', date.toLocaleString(undefined, {
				month: 'long', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit', timeZoneName: 'short',
			}));
		}
		if (viewer.is_guest !== Boolean(form.elements.namedItem('name'))) {
			viewer = { ...viewer, can_post: false, message: labels.session_expired };
		}
		viewerLabel.textContent = viewer.message || (viewer.name ? labels.posting_as.replace(':name', viewer.name) : '');
		fresh = true;
		status.textContent = '';
		status.hidden = true;
		updateSend();
	};
	const schedule = () => {
		clearTimeout(timer);
		if (isActive()) timer = setTimeout(refresh, 3000);
	};
	async function refresh() {
		clearTimeout(timer);
		if (!isActive()) return;
		if (activeRequest || posting) {
			refreshPending = true;
			return;
		}
		refreshPending = false;
		refreshButton.disabled = true;
		activeRequest = request(box.dataset.feed);
		updateSend();
		try {
			render(await activeRequest);
		} catch (failure) {
			console.error('Chat refresh failed', failure);
			fresh = false;
			status.textContent = labels.stale;
			status.hidden = false;
			updateSend();
		} finally {
			activeRequest = null;
			updateSend();
			refreshButton.disabled = false;
			if (refreshPending && isActive()) {
				refreshPending = false;
				void refresh();
			} else schedule();
		}
	}
	form.elements.body.addEventListener('keydown', (event) => {
		if (event.key !== 'Enter' || event.shiftKey || event.isComposing || event.keyCode === 229) return;
		event.preventDefault();
		if (!event.repeat && !send.disabled) form.requestSubmit(send);
	});
	form.addEventListener('submit', async (event) => {
		event.preventDefault();
		if (posting || activeRequest || !fresh || !viewer?.can_post) return;
		posting = true;
		const submittedBody = form.elements.body.value;
		clearTimeout(timer);
		updateSend();
		showError('');
		try {
			render(await request(form.action, {
				method: 'POST',
				headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
				body: new FormData(form),
			}));
			if (form.elements.body.value === submittedBody) form.elements.body.value = '';
		} catch (failure) {
			console.error('Chat submission failed', failure);
			showError(`${failure.message} ${labels.failed}`);
		} finally {
			posting = false;
			updateSend();
			if (refreshPending) void refresh();
			else schedule();
		}
	});
	toggle.addEventListener('click', () => {
		content.hidden = !content.hidden;
		toggle.setAttribute('aria-expanded', String(!content.hidden));
		toggle.textContent = content.hidden ? '+' : '−';
		try { localStorage.setItem('chatbox-collapsed', String(content.hidden)); }
		catch (failure) { console.warn('Chat preference could not be saved', failure); }
		clearTimeout(timer);
		if (isActive()) void refresh();
	});
	refreshButton.addEventListener('click', refresh);
	document.addEventListener('visibilitychange', () => {
		clearTimeout(timer);
		if (isActive()) void refresh();
	});
	window.addEventListener('pagehide', () => { stopped = true; clearTimeout(timer); });
	window.addEventListener('pageshow', (event) => {
		if (event.persisted) { stopped = false; void refresh(); }
	});
	try {
		content.hidden = localStorage.getItem('chatbox-collapsed') === 'true';
		toggle.setAttribute('aria-expanded', String(!content.hidden));
		toggle.textContent = content.hidden ? '+' : '−';
	} catch (failure) { console.warn('Chat preference could not be read', failure); }
	void refresh();
}
