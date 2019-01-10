todo
====

- upravit readme

## PR
- pockat az se vyda code-fixer
- pripravit male PR
    - code-style fix pred mergem (commenty u mixed)         [merged]
    - feature/update-dependencies                           [merged]
        - php stan - assertion
    - forbid-nested-value-and-filterable                    [merged]
    - feature/change-filter-title                           [merged -> `add-filter-factory`]
        - [a-zA-Z...] + fullTitle
    - feature/extend-enumerable-interface-by-filters-interface [merged]
        - add Filters::dump method for better testing
        - add IEnumerable
    - feature/add-apifilter-exception                       [merged]
    - feature/add-filter-factory                            [merged -> `parsery`]
    
    - parsery                                               [merged]
        - prvni                                             [merged]
            - interface
            - abstract parser + test case
            - query parmeter parser + test?
                - tady bude parse -> parseOld a v parse bude nove reseni + fallback na parseOld
            - pridan `SingleColumnSingleValueParser` + test (jako nejjednodussi)
        - dalsi...
            - `SingleColumnArrayValueParser` + test         [merged]
            - `TupleColumnArrayValueParser` + test          [merged]
            - `TupleColumnTupleValueParser` + test          [merged]
            - `UnsupportedTupleCombinationParser` + test    [merged]
        - finale                                            [merged]
            - zrusit parseOld metodu a dalsi private co tam jsou
            - pridat info do readme
    
    - _kouknout dolu na TODO_
    
    - FILTER FUNCTION
        - FilterFunction                                    [merged]
        - FilterParameter                                   [merged]
        - FilterFactory                                     [merged]
            - *add test to FilterFactory*
        - Entity/Parameter                                  [merged]
        - Functions
            - `register` + `get`                            [merged]
        - FunctionParser
            - `Abstract` + base stuff                       [merged]
            - `AbstractFunctionParser`                      [merged]
            - `ExplicitFunctionDefinitionInValueParser`     [merged]
                + `AbstractFunctionParser::markColumnAsParsed()`
                + `Functions::getParametersFor()` + test
                + `FunctionParserTest::shouldNotParseFunctionByExplicitValueDefinition()`
            - `ExplicitFunctionDefinitionParser`            [merged]
                + `FunctionParserTest::shouldNotParseFunctionDefinedBadly()`
                + `FunctionParserTest::shouldNotCallOneFunctionTwice()`
            - `ImplicitFunctionDefinitionByValueParser`     [merged]
                + `Functions::getFunctionNamesByParameter()` + test
            - `ExplicitFunctionDefinitionByTupleParser`     [merged]
                + `AbstractFunctionParser::validateTupleValue()`
                + `FunctionParserTest::shouldNotCallOneFunctionTwiceByDifferentDefinitions()`
            - `ImplicitFunctionDefinitionByTupleParser`     [merged]
                + `Functions::getFunctionNamesByAllParameters()` + test
            - rename `ExplicitFunctionDefinitionInValueParser` -> `ExplicitFunctionDefinitionByValueParser` [merged]
            - code-style fix po mergi (odebrat zbytecne commenty u mixed) [merged]
            - `FunctionInFilterParameterParser`             [merged]
            - update
                - QueryParameterParserTest (asi uz je ...)  [PR]
        - applicator
            + `Functions::getParameterDefinitionsFor()` + test

    - REGISTER FUNCTION
        - ApiFilterTest
        - Readme
        - register function
        - Functions
    
    - DECLARE FUNCTION
        - parameters
        - function creator
        - declare function
    
    - execute function
        + `Functions::execute` + test
    - apply function
    
    - pridat moznost i z `?filter[]=(spot,common,all)`      [todo]
        - povolit i `?filter=(single,filter,only)` ?
