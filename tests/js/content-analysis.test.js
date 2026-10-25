const test = require('node:test');
const assert = require('node:assert/strict');
const { analyze } = require('../../assets/js/content-analysis.js');

const title = 'SEO Titel mit passendem Fokus Keyword für Besucher';
const description = `Fokus Keyword ${'hilfreiche Informationen '.repeat(5)}`;
const content = `Fokus Keyword ${'wertvoller '.repeat(298)}`;

test('awards a full score when all SEO checks pass', () => {
	const result = analyze({ title, description, keyword: 'Fokus Keyword', content });

	assert.equal(result.score, 100);
	assert.equal(result.wordCount, 300);
	assert.ok(result.checks.every((check) => 'good' === check.status));
});

test('reports missing metadata and keyword without awarding their points', () => {
	const result = analyze({ content: 'Short content' });

	assert.equal(result.score, 0);
	assert.deepEqual(
		result.checks.map((check) => check.key),
		[
			'titleMissing',
			'descriptionMissing',
			'keywordMissing',
			'contentLengthImprove',
		]
	);
});

test('normalizes keyword matching and ignores markup when counting words', () => {
	const result = analyze({
		title: 'Ein SEO-Titel mit Fokus Keyword für die Vorschau',
		description: 'Eine Meta-Beschreibung mit Fokus Keyword, die ausreichend viele Zeichen für das Suchergebnis und interessierte Besucher enthält.',
		keyword: 'fokus   keyword',
		content: `<p>${'Inhalt '.repeat(300)}</p>`,
	});

	assert.equal(result.wordCount, 300);
	assert.ok(result.checks.some((check) => 'keywordInTitle' === check.key));
	assert.ok(result.checks.some((check) => 'keywordInDescription' === check.key));
	assert.ok(result.checks.some((check) => 'keywordNotInContent' === check.key));
});
