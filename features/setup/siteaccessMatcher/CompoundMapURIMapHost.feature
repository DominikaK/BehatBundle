Feature: Setup basic URI Element Siteaccess matching configuration

  @admin
  Scenario: Add a language. Create a siteaccess using it and add it to PageBuilder
    Given Language "Polski" with code "pol-PL" exists
    And I add a siteaccess "test" to "site_group" with settings
      | key       | value  |
      | languages | pol-PL,eng-GB |
    And I "set" siteaccess matcher configuration of type "Compound\LogicalAnd"
    """
      site:
          matchers:
              Map\URI:
                  st: true
              Map\Host:
                  ezenv.local: true
          match: site
      test:
          matchers:
              Map\URI:
                  tst: true
              Map\Host:
                  test-ezenv.local: true
          match: test
      admin:
          matchers:
              Map\URI:
                  adm: true
              Map\Host:
                  admin-ezenv.local: true
          match: admin
    """
    And I append configuration to "admin_group" siteaccess
      | key                          | value  |
      | languages                    | pol-PL |
      | page_builder.siteaccess_list | test   |