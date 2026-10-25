document.addEventListener('DOMContentLoaded', () => {
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
				input.value = frame.state().get('selection').first().get('id');
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
			checks.push('SEO-Titel hat eine gute Laenge.');
		} else {
			checks.push('SEO-Titel sollte 30 bis 60 Zeichen lang sein.');
		}

		if (descriptionValue.length >= 120 && descriptionValue.length <= 160) {
			score += 30;
			checks.push('Meta-Beschreibung hat eine gute Laenge.');
		} else {
			checks.push('Meta-Beschreibung sollte 120 bis 160 Zeichen lang sein.');
		}

		if (keywordValue) {
			score += 20;
			if (titleValue.toLowerCase().includes(keywordValue)) {
				score += 10;
				checks.push('Fokus-Keyword ist im SEO-Titel enthalten.');
			}
			if (descriptionValue.toLowerCase().includes(keywordValue)) {
				score += 10;
				checks.push('Fokus-Keyword ist in der Meta-Beschreibung enthalten.');
			}
		} else {
			checks.push('Kein Fokus-Keyword gesetzt.');
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
