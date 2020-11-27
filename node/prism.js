import readline from 'readline';
import Prism from 'prismjs';

function fail(error) {
    process.stderr.write(error);
    process.stderr.write('\n');
    process.stderr.write('DONE');
    process.stderr.write('\n');
}

function success(result) {
    process.stdout.write(result);
    process.stderr.write('\n');
    process.stderr.write('DONE');
    process.stderr.write('\n');
}

function parse(input) {
    try {
        return JSON.parse(input);
    } catch (error) {
        return error;
    }
}

function onInput(input) {
    let query, result;

    try {
        query = JSON.parse(input);
    } catch (error) {
        return fail(`Could not parse JSON query: ${error.message}.`);
    }

    const { language, value } = query;

    if (typeof value === 'undefined') {
        return fail('Missing "value" property in JSON query.');
    }

    if (typeof language === 'undefined') {
        return fail('Missing "language" property in JSON query.');
    }

    if (typeof Prism.languages[language] === 'undefined') {
        return fail(`Unsupported language "${language}".`);
    }

    try {
        result = Prism.highlight(value, Prism.languages[language], language);
    } catch (error) {
        return fail(`Highlight process failed: ${error.message}.`);
    }

    return success(result);
}

const server = readline.createInterface({
    input: process.stdin,
    output: process.stdout,
    terminal: false,
});

server.on('line', onInput);
