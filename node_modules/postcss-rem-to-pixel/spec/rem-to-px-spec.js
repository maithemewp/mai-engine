// Jasmine unit tests
// To run tests, run these commands from the project root:
// 1. `npm install -g jasmine-node`
// 2. `jasmine-node spec`

/* global describe, it, expect */

'use strict';
var postcss = require('postcss');
var pxToRem = require('postcss-pxtorem');
var remToPx = require('..');

var basicCSS = '.rule { font-size: 0.9375rem }';
var filterPropList = require('../lib/filter-prop-list');

describe('remToPx', function () {
    it('should work on the readme example', function () {
        var input = 'h1 { margin: 0 0 20px; font-size: 2rem; line-height: 1.2; letter-spacing: 0.0625rem; }';
        var output = 'h1 { margin: 0 0 20px; font-size: 32px; line-height: 1.2; letter-spacing: 1px; }';
        var processed = postcss(remToPx()).process(input).css;

        expect(processed).toBe(output);
    });

    it('should replace the rem unit with px', function () {
        var processed = postcss(remToPx()).process(basicCSS).css;
        var expected = '.rule { font-size: 15px }';

        expect(processed).toBe(expected);
    });

    it('should ignore non rem properties', function () {
        var expected = '.rule { font-size: 2em }';
        var processed = postcss(remToPx()).process(expected).css;

        expect(processed).toBe(expected);
    });

    it('should handle < 1 values and values without a leading 0', function () {
        var rules = '.rule { margin: 0.5px .03125rem -0.0125rem -.2em }';
        var expected = '.rule { margin: 0.5px 0.5px -0.2px -.2em }';
        var options = {
            propList: ['margin']
        };
        var processed = postcss(remToPx(options)).process(rules).css;

        expect(processed).toBe(expected);
    });

    it('should not add properties that already exist', function () {
        var expected = '.rule { font-size: 1rem; font-size: 16px; }';
        var processed = postcss(remToPx()).process(expected).css;

        expect(processed).toBe(expected);
    });

    it('should remain unitless if 0', function () {
        var expected = '.rule { font-size: 0rem; font-size: 0; }';
        var processed = postcss(remToPx()).process(expected).css;

        expect(processed).toBe(expected);
    });
});

describe('value parsing', function () {
    it('should not replace values in double quotes or single quotes', function () {
        var options = {
            propList: ['*']
        };
        var rules = '.rule { content: \'1rem\'; font-family: "1rem"; font-size: 1rem; }';
        var expected = '.rule { content: \'1rem\'; font-family: "1rem"; font-size: 16px; }';
        var processed = postcss(remToPx(options)).process(rules).css;

        expect(processed).toBe(expected);
    });

    it('should not replace values in `url()`', function () {
        var options = {
            propList: ['*']
        };
        var rules = '.rule { background: url(1rem.jpg); font-size: 1rem; }';
        var expected = '.rule { background: url(1rem.jpg); font-size: 16px; }';
        var processed = postcss(remToPx(options)).process(rules).css;

        expect(processed).toBe(expected);
    });

    it('should not replace values with an uppercase R or REM', function () {
        var options = {
            propList: ['*']
        };
        var rules = '.rule { margin: 0.75rem calc(100% - 14REM); height: calc(100% - 1.25rem); font-size: 12Rem; line-height: 1rem; }';
        var expected = '.rule { margin: 12px calc(100% - 14REM); height: calc(100% - 20px); font-size: 12Rem; line-height: 16px; }';
        var processed = postcss(remToPx(options)).process(rules).css;

        expect(processed).toBe(expected);
    });
});

describe('rootValue', function () {
    it('should replace using a root value of 10', function () {
        var expected = '.rule { font-size: 9.375px }';
        var options = {
            rootValue: 10
        };
        var processed = postcss(remToPx(options)).process(basicCSS).css;

        expect(processed).toBe(expected);
    });
});

describe('unitPrecision', function () {
    it('should replace using a decimal of 2 places', function () {
        var rules = '.rule { font-size: 0.534375rem }';
        var expected = '.rule { font-size: 8.55px }';
        var options = {
            unitPrecision: 2
        };
        var processed = postcss(remToPx(options)).process(rules).css;

        expect(processed).toBe(expected);
    });
});

describe('propList', function () {

    it('should only replace properties in the prop list', function () {
        var rules    = '.rule { font-size: 1rem; margin: 1rem; margin-left: 0.5rem; padding: 0.5rem; padding-right: 1rem }';
        var expected = '.rule { font-size: 16px; margin: 16px; margin-left: 0.5rem; padding: 0.5rem; padding-right: 16px }';
        var options = {
            propList: ['*font*', 'margin*', '!margin-left', '*-right', 'pad']
        };
        var processed = postcss(remToPx(options)).process(rules).css;

        expect(processed).toBe(expected);
    });

    it('should only replace properties in the prop list with wildcard', function () {
        var rules    = '.rule { font-size: 1rem; margin: 1rem; margin-left: 0.5rem; padding: 0.5rem; padding-right: 1rem }';
        var expected = '.rule { font-size: 1rem; margin: 16px; margin-left: 0.5rem; padding: 0.5rem; padding-right: 1rem }';
        var options = {
            propList: ['*', '!margin-left', '!*padding*', '!font*']
        };
        var processed = postcss(remToPx(options)).process(rules).css;

        expect(processed).toBe(expected);
    });

    it('should replace all properties when white list is wildcard', function () {
        var rules    = '.rule { margin: 1rem; font-size: 0.9375rem }';
        var expected = '.rule { margin: 16px; font-size: 15px }';
        var options = {
            propList: ['*']
        };
        var processed = postcss(remToPx(options)).process(rules).css;

        expect(processed).toBe(expected);
    });
});

describe('selectorBlackList', function () {
    it('should ignore selectors in the selector black list', function () {
        var rules = '.rule { font-size: 0.9375rem } .rule2 { font-size: 15rem }';
        var expected = '.rule { font-size: 15px } .rule2 { font-size: 15rem }';
        var options = {
            selectorBlackList: ['.rule2']
        };
        var processed = postcss(remToPx(options)).process(rules).css;

        expect(processed).toBe(expected);
    });

    it('should ignore every selector with `body$`', function () {
        var rules = 'body { font-size: 1rem; } .class-body$ { font-size: 16rem; } .simple-class { font-size: 1rem; }';
        var expected = 'body { font-size: 16px; } .class-body$ { font-size: 16rem; } .simple-class { font-size: 16px; }';
        var options = {
            selectorBlackList: ['body$']
        };
        var processed = postcss(remToPx(options)).process(rules).css;

        expect(processed).toBe(expected);
    });

    it('should only ignore exactly `body`', function () {
        var rules = 'body { font-size: 16rem; } .class-body { font-size: 1rem; } .simple-class { font-size: 1rem; }';
        var expected = 'body { font-size: 16rem; } .class-body { font-size: 16px; } .simple-class { font-size: 16px; }';
        var options = {
            selectorBlackList: [/^body$/]
        };
        var processed = postcss(remToPx(options)).process(rules).css;

        expect(processed).toBe(expected);
    });
});

describe('replace', function () {
    it('should leave fallback pixel unit with root em value', function () {
        var options = {
            replace: false
        };
        var expected = '.rule { font-size: 0.9375rem; font-size: 15px }';
        var processed = postcss(remToPx(options)).process(basicCSS).css;

        expect(processed).toBe(expected);
    });
});

describe('mediaQuery', function () {
    it('should replace rem in media queries', function () {
        var rules = '@media (min-width: 31.25rem) { .rule { font-size: 1rem } }';
        var expected = '@media (min-width: 500px) { .rule { font-size: 16px } }';
        var options = {
            mediaQuery: true
        };
        var processed = postcss(remToPx(options)).process(rules).css;

        expect(processed).toBe(expected);
    });
});

describe('minRemValue', function () {
    it('should not replace values below minRemValue', function () {
        var rules = '.rule { border: 0.0625rem solid #000; font-size: 1rem; margin: 0.0625rem 0.625rem; }';
        var expected = '.rule { border: 0.0625rem solid #000; font-size: 16px; margin: 0.0625rem 10px; }';
        var options = {
            propList: ['*'],
            minRemValue: 0.5
        };
        var processed = postcss(remToPx(options)).process(rules).css;

        expect(processed).toBe(expected);
    });
});

describe('pxToRem', function () {
    it('should convert from px to rem and back using postcss-pxtorem', function () {
        var input = 'h1 { margin: 0 0 20px 0.5rem; font-size: 13px; line-height: 1.2; letter-spacing: 1px; }';
        var toRems = postcss(pxToRem()).process(input).css;
        var processed = postcss(remToPx()).process(toRems).css;

        expect(processed).toBe(input);
    });
});

describe('filter-prop-list', function () {
    it('should find "exact" matches from propList', function () {
        var propList = ['font-size', 'margin', '!padding', '*border*', '*', '*y', '!*font*'];
        var expected = 'font-size,margin';
        expect(filterPropList.exact(propList).join()).toBe(expected);
    });

    it('should find "contain" matches from propList and reduce to string', function () {
        var propList = ['font-size', '*margin*', '!padding', '*border*', '*', '*y', '!*font*'];
        var expected = 'margin,border';
        expect(filterPropList.contain(propList).join()).toBe(expected);
    });

    it('should find "start" matches from propList and reduce to string', function () {
        var propList = ['font-size', '*margin*', '!padding', 'border*', '*', '*y', '!*font*'];
        var expected = 'border';
        expect(filterPropList.startWith(propList).join()).toBe(expected);
    });

    it('should find "end" matches from propList and reduce to string', function () {
        var propList = ['font-size', '*margin*', '!padding', 'border*', '*', '*y', '!*font*'];
        var expected = 'y';
        expect(filterPropList.endWith(propList).join()).toBe(expected);
    });

    it('should find "not" matches from propList and reduce to string', function () {
        var propList = ['font-size', '*margin*', '!padding', 'border*', '*', '*y', '!*font*'];
        var expected = 'padding';
        expect(filterPropList.notExact(propList).join()).toBe(expected);
    });

    it('should find "not contain" matches from propList and reduce to string', function () {
        var propList = ['font-size', '*margin*', '!padding', '!border*', '*', '*y', '!*font*'];
        var expected = 'font';
        expect(filterPropList.notContain(propList).join()).toBe(expected);
    });

    it('should find "not start" matches from propList and reduce to string', function () {
        var propList = ['font-size', '*margin*', '!padding', '!border*', '*', '*y', '!*font*'];
        var expected = 'border';
        expect(filterPropList.notStartWith(propList).join()).toBe(expected);
    });

    it('should find "not end" matches from propList and reduce to string', function () {
        var propList = ['font-size', '*margin*', '!padding', '!border*', '*', '!*y', '!*font*'];
        var expected = 'y';
        expect(filterPropList.notEndWith(propList).join()).toBe(expected);
    });
});
