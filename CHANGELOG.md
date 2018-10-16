# Changelog

<!-- We follow Semantic Versioning (https://semver.org/) and Keep a Changelog principles (https://keepachangelog.com/) -->
<!-- There should always be "Unreleased" section at the beginning. -->

## Unreleased
- Allow arrow in `Tuple`
- Allow a specific filter from columns in `Tuple`
- Add implicit `IN` filter for column in `Tuple` with array value
- Fix parsing `lte` filter
- Add `ApiFilterException` to covers all internal exceptions
- Allow register a `function` to `ApiFilter`

## 1.0.0 - 2018-08-28
- Initial version.
    - **Filters**
        - Equal to
        - Lower than 
        - Lower or equal than
        - Greater than
        - Greater or equal than
        - IN
    - **Applicators**
        - Doctrine Query Builder
        - _Naive_ SQL 
    - **Tuple** allowed in
        - Equal to
        - Lower than 
        - Lower or equal than
        - Greater than
        - Greater or equal than
