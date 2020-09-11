const { readFileSync } = require('fs');
const Prism = require('prismjs');
const loadLanguages = require('prismjs/components/');
const [language, value] = process.argv.slice(2);

loadLanguages([language]);

const content = Prism.highlight(value, Prism.languages[language], language);

process.stdout.write(content, () => process.exit(0));
