document.addEventListener('DOMContentLoaded', () => {
	const settingsTabs = [...document.querySelectorAll('[data-ps-seo-settings-tab]')];
	const settingsPanels = [...document.querySelectorAll('[data-ps-seo-settings-panel]')];

	if (settingsTabs.length && settingsPanels.length) {
		const activateSettingsTab = (key) => {
			const activeTab = settingsTabs.find((tab) => tab.dataset.psSeoSettingsTab === key) || settingsTabs[0];
			const activeKey = activeTab.dataset.psSeoSettingsTab;

			settingsTabs.forEach((tab) => {
				const isActive = tab === activeTab;
				tab.classList.toggle('is-active', isActive);
				tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
			});
			settingsPanels.forEach((panel) => {
				panel.hidden = panel.dataset.psSeoSettingsPanel !== activeKey;
			});

			window.localStorage.setItem('psSeoSettingsTab', activeKey);
			window.history.replaceState(null, '', `#ps-seo-${activeKey}`);
		};

		settingsTabs.forEach((tab) => {
			tab.addEventListener('click', () => activateSettingsTab(tab.dataset.psSeoSettingsTab));
		});

		const hashTab = window.location.hash.replace('#ps-seo-', '');
		const storedTab = window.localStorage.getItem('psSeoSettingsTab');
		activateSettingsTab(hashTab || storedTab || 'overview');
	}

	document.querySelectorAll('.ps-seo-module-card input[type="checkbox"]').forEach((toggle) => {
		toggle.addEventListener('change', () => {
			toggle.closest('.ps-seo-module-card')?.classList.toggle('is-enabled', toggle.checked);
		});
	});

	document.querySelectorAll('[data-ps-seo-tab]').forEach((button) => {
		button.addEventListener('click', () => {
			const container = button.closest('.ps-seo-metabox');
			if (!container) {
				return;
			}

			container.querySelectorAll('[data-ps-seo-tab]').forEach((tab) => tab.classList.remove('is-active'));
			container.querySelectorAll('[data-ps-seo-panel]').forEach((panel) => {
				panel.hidden = panel.dataset.psSeoPanel !== button.dataset.psSeoTab;
			});
			button.classList.add('is-active');
		});
	});

	document.querySelectorAll('.ps-seo-select-media').forEach((button) => {
		button.addEventListener('click', () => {
			if (!window.wp?.media) {
				return;
			}

			const input = button.parentElement.querySelector('input');
			const frame = window.wp.media({
				title: button.textContent.trim(),
				button: { text: button.textContent.trim() },
				multiple: false,
			});

			frame.on('select', () => {
				const attachment = frame.state().get('selection').first();
				input.value = attachment.get('id');

				const panel = button.closest('[data-ps-seo-settings-panel]');
				const preview = panel?.querySelector('[data-ps-seo-media-preview]');
				if (preview) {
					const sizes = attachment.get('sizes') || {};
					const source = sizes.medium?.url || sizes.thumbnail?.url || attachment.get('url');
					preview.style.backgroundImage = `url("${source}")`;
					preview.querySelector('.dashicons')?.remove();
				}
			});
			frame.open();
		});
	});

	const title = document.querySelector('[data-ps-seo-title]');
	const description = document.querySelector('[data-ps-seo-description]');
	const keyword = document.querySelector('[data-ps-seo-keyword]');

	if (!title || !description || !keyword) {
		return;
	}

	const updateAnalysis = () => {
		const titleValue = title.value.trim();
		const descriptionValue = description.value.trim();
		const keywordValue = keyword.value.trim().toLowerCase();
		let score = 0;
		const checks = [];

		document.querySelector('[data-ps-seo-title-count]').textContent = titleValue.length;
		document.querySelector('[data-ps-seo-description-count]').textContent = descriptionValue.length;

		if (titleValue.length >= 30 && titleValue.length <= 60) {
			score += 30;
			checks.push(psSeoAdmin.titleLengthGood);
		} else {
			checks.push(psSeoAdmin.titleLengthImprove);
		}

		if (descriptionValue.length >= 120 && descriptionValue.length <= 160) {
			score += 30;
			checks.push(psSeoAdmin.descriptionLengthGood);
		} else {
			checks.push(psSeoAdmin.descriptionImprove);
		}

		if (keywordValue) {
			score += 20;
			if (titleValue.toLowerCase().includes(keywordValue)) {
				score += 10;
				checks.push(psSeoAdmin.keywordInTitle);
			}
			if (descriptionValue.toLowerCase().includes(keywordValue)) {
				score += 10;
				checks.push(psSeoAdmin.keywordInDescription);
			}
		} else {
			checks.push(psSeoAdmin.keywordMissing);
		}

		const scoreElement = document.querySelector('[data-ps-seo-score]');
		const list = document.querySelector('[data-ps-seo-analysis-list]');
		if (!scoreElement || !list) {
			return;
		}

		scoreElement.textContent = score;
		scoreElement.classList.toggle('is-good', score >= 80);
		scoreElement.classList.toggle('is-warning', score >= 50 && score < 80);
		list.replaceChildren(...checks.map((check) => {
			const item = document.createElement('li');
			item.textContent = check;
			return item;
		}));
	};

	[title, description, keyword].forEach((field) => field.addEventListener('input', updateAnalysis));
	updateAnalysis();
});
