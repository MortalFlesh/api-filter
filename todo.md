todo
====

- upravit readme

## PR
- pockat az se vyda code-fixer
- pripravit male PR
    - ~~code-style fix pred mergem (commenty u mixed)~~     [merged]
    - feature/add-apifilter-exception                       [PR]
    - forbid-nested-value-and-filterable                    [PR]
    - feature/change-filter-title                           [PR -> rebase and send `add-filter-factory`]
        - [a-zA-Z...] + fullTitle
    
    - feature/extend-enumerable-interface-by-filters-interface [PR]
        - add Filters::dump method for better testing
        - add IEnumerable
        
    - feature/update-dependencies                           [PR]
        - php stan - assertion
    
    - feature/add-filter-factory                            [prepared but waits for `change-filter-title` -> `parsery`]
    
    - parsery                                               [waits for `feature/add-filter-factory`]
        - prvni
            - interface
            - abstract parser + test case
            - query parmeter parser + test?
                - tady bude parse -> parseOld a v parse bude nove reseni + fallback na parseOld
            - pridan `SingleColumnSingleValueParser` + test (jako nejjednodussi)
        - dalsi...
            - `SingleColumnArrayValueParser` + test 
            - `UnsupportedTupleCombinationParser` + test 
            - `TupleColumnArrayValueParser` + test 
            - `TupleColumnTupleValueParser` + test
        - finale
            - zrusit parseOld metodu a dalsi private co tam jsou
    
    - FILTER FUNCTION
        - FilterFunction 
            - *add test to FilterFactory*
        - FilterParameter
            - *add test to FilterFactory*
        - FunctionParser
        - applicator
    
    - REGISTER FUNCTION
        - register function
        - Functions
    
    - DECLARE FUNCTION
        - parameters
        - function creator
        - declare function
    
    - execute function
    - apply function
    
    - pridat moznost i z `?filter[]=(spot,common,all)`      [todo]
    
    - code-style fix po mergi (odebrat zbytecne commenty u mixed)
