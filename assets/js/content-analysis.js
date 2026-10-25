(function (root, factory) {
	const api = factory();

	if (typeof module === 'object' && module.exports) {
		module.exports = api;
	} else {
		root.psSeoContentAnalysis = api;
	}
}(typeof globalThis !== 'undefined' ? globalThis : this, () => {
	const characterCount = (value) => Array.from(String(value || '')).length;

	const normalize = (value) => String(value || '')
		.toLocaleLowerCase()
		.replace(/\s+/g, ' ')
		.trim();

	const plainText = (value) => String(value || '')
		.replace(/<[^>]*>/g, ' ')
		.replace(/&nbsp;/gi, ' ')
		.replace(/\s+/g, ' ')
		.trim();

	const wordCount = (value) => {
		const text = plainText(value);

		return text ? text.split(/\s+/).length : 0;
	};

	const includesKeyword = (value, keyword) => normalize(value).includes(normalize(keyword));

	const analyze = ({ title = '', description = '', keyword = '', content = '' } = {}) => {
		const checks = [];
		const titleLength = characterCount(title);
		const descriptionLength = characterCount(description);
		const words = wordCount(content);
		const hasKeyword = '' !== normalize(keyword);
		let score = 0;

		if (titleLength >= 30 && titleLength <= 60) {
			score += 20;
			checks.push({ key: 'titleLengthGood', status: 'good' });
		} else {
			checks.push({ key: titleLength ? 'titleLengthImprove' : 'titleMissing', status: 'warning' });
		}

		if (descriptionLength >= 120 && descriptionLength <= 160) {
			score += 20;
			checks.push({ key: 'descriptionLengthGood', status: 'good' });
		} else {
			checks.push({ key: descriptionLength ? 'descriptionLengthImprove' : 'descriptionMissing', status: 'warning' });
		}

		if (!hasKeyword) {
			checks.push({ key: 'keywordMissing', status: 'warning' });
		} else {
			score += 10;
			checks.push({ key: 'keywordSet', status: 'good' });

			[
				{ value: title, success: 'keywordInTitle', failure: 'keywordNotInTitle', points: 15 },
				{ value: description, success: 'keywordInDescription', failure: 'keywordNotInDescription', points: 15 },
				{ value: content, success: 'keywordInContent', failure: 'keywordNotInContent', points: 10 },
			].forEach((check) => {
				if (includesKeyword(check.value, keyword)) {
					score += check.points;
					checks.push({ key: check.success, status: 'good' });
				} else {
					checks.push({ key: check.failure, status: 'warning' });
				}
			});
		}

		if (words >= 300) {
			score += 10;
			checks.push({ key: 'contentLengthGood', status: 'good' });
		} else {
			checks.push({ key: 'contentLengthImprove', status: 'warning' });
		}

		return {
			score,
			checks,
			titleLength,
			descriptionLength,
			wordCount: words,
		};
	};

	return { analyze };
}));
