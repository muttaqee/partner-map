CREATE TABLE table_types_meta (
      id INT(10) NOT NULL AUTO_INCREMENT,
      name VARCHAR(50) NOT NULL UNIQUE,
      PRIMARY KEY (id)
    ) COMMENT 'Table types';
CREATE TABLE tables_meta (
      id INT(10) NOT NULL AUTO_INCREMENT,
      name VARCHAR(50) NOT NULL UNIQUE,   /* Name in db */
      label VARCHAR(50),                  /* Name to display in UI */
      type VARCHAR(50) NOT NULL,          /* Functional category */
      is_searchable BIT(1) NOT NULL DEFAULT 0, /* Tables entities searchable from main UI (useful for blocking out uninteresting _primary tables) */
      rating_table VARCHAR(50),           /* Accompanying ratings table, if there is one (useful if there is more than one rating lookup table) */
      PRIMARY KEY (id),
      FOREIGN KEY (type) REFERENCES table_types_meta(name)
    ) COMMENT 'Tables';
CREATE TABLE table_fk_meta (
      table_id INT(10) NOT NULL,
      reference_table_id INT(10) NOT NULL,
      fk_column VARCHAR(50) NOT NULL,
      relationship_to_reference VARCHAR(50) DEFAULT null,
      CONSTRAINT pk PRIMARY KEY (table_id, reference_table_id)
    ) COMMENT 'Table references';
CREATE TABLE ratings (
      id INT(10) NOT NULL AUTO_INCREMENT,
      name VARCHAR(10) NOT NULL UNIQUE COMMENT 'Rating',
      PRIMARY KEY (id)
    ) COMMENT 'Ratings';
CREATE TABLE ratings_simple (
      id INT(10) NOT NULL AUTO_INCREMENT,
      name VARCHAR(10) NOT NULL UNIQUE COMMENT 'Rating',
      PRIMARY KEY (id)
    ) COMMENT 'Ratings';
CREATE TABLE partners (
      id INT(10) NOT NULL AUTO_INCREMENT,
      name VARCHAR(50) NOT NULL COMMENT 'Name',
      is_partner_plus BIT(1) COMMENT 'Partner Plus', /* FIXME: Remove and save for opportunity_partner_junction? */
      notes VARCHAR(500) COMMENT 'Notes',
      PRIMARY KEY (id)
    ) COMMENT 'Partners';
CREATE TABLE partner_strengths (
      id INT(10) NOT NULL AUTO_INCREMENT,
      name VARCHAR(50) NOT NULL UNIQUE COMMENT 'Strength',
      PRIMARY KEY (id)
    ) COMMENT 'Partner strengths';
CREATE TABLE partner_strength_ratings (
      primary_id INT(10) NOT NULL COMMENT 'Partner',
      lookup_id INT(10) NOT NULL COMMENT 'Strength',
      rating_id INT(10) NOT NULL COMMENT 'Rating',
      CONSTRAINT pk PRIMARY KEY (primary_id, lookup_id),
      FOREIGN KEY (primary_id) REFERENCES partners(id),
      FOREIGN KEY (lookup_id) REFERENCES partner_strengths(id),
      FOREIGN KEY (rating_id) REFERENCES ratings_simple(id)
    ) COMMENT 'Partner strength ratings';
CREATE TABLE technologies (
      id INT(10) NOT NULL AUTO_INCREMENT,
      type VARCHAR(50) NOT NULL COMMENT 'Category',
      name VARCHAR(50) NOT NULL COMMENT 'Technology',
      PRIMARY KEY (id),
      CONSTRAINT UNIQUE (type, name)
    ) COMMENT 'Technologies';
CREATE TABLE partner_technology_ratings (
      primary_id INT(10) NOT NULL COMMENT 'Partner',
      lookup_id INT(10) NOT NULL COMMENT 'Technology',
      rating_id INT(10) NOT NULL COMMENT 'Rating',
      CONSTRAINT pk PRIMARY KEY (primary_id, lookup_id),
      FOREIGN KEY (primary_id) REFERENCES partners(id),
      FOREIGN KEY (lookup_id) REFERENCES technologies(id),
      FOREIGN KEY (rating_id) REFERENCES ratings_simple(id)
    ) COMMENT 'Partner technology ratings';
CREATE TABLE solutions (
      id INT(10) NOT NULL AUTO_INCREMENT,
      type VARCHAR(50) NOT NULL COMMENT 'Category',
      name VARCHAR(50) NOT NULL COMMENT 'Solution',
      PRIMARY KEY (id),
      CONSTRAINT UNIQUE (type, name)
    ) COMMENT 'Solutions';
CREATE TABLE partner_solution_ratings (
      primary_id INT(10) NOT NULL COMMENT 'Partner',
      lookup_id INT(10) NOT NULL COMMENT 'Solution',
      rating_id INT(10) NOT NULL COMMENT 'Rating',
      CONSTRAINT pk PRIMARY KEY (primary_id, lookup_id),
      FOREIGN KEY (primary_id) REFERENCES partners(id),
      FOREIGN KEY (lookup_id) REFERENCES solutions(id),
      FOREIGN KEY (rating_id) REFERENCES ratings_simple(id)
    ) COMMENT 'Partner solution ratings';
CREATE TABLE misc (
      id INT(10) NOT NULL AUTO_INCREMENT,
      name VARCHAR(50) NOT NULL UNIQUE COMMENT 'Miscellaneous',
      PRIMARY KEY (id)
    ) COMMENT 'Miscellaneous';
CREATE TABLE partner_misc_ratings (
      primary_id INT(10) NOT NULL COMMENT 'Partner',
      lookup_id INT(10) NOT NULL COMMENT 'Miscellaneous',
      rating_id INT(10) NOT NULL COMMENT 'Rating',
      CONSTRAINT pk PRIMARY KEY (primary_id, lookup_id),
      FOREIGN KEY (primary_id) REFERENCES partners(id),
      FOREIGN KEY (lookup_id) REFERENCES misc(id),
      FOREIGN KEY (rating_id) REFERENCES ratings_simple(id)
    ) COMMENT 'Partner miscellaneous ratings';
CREATE TABLE verticals (
      id INT(10) NOT NULL AUTO_INCREMENT,
      name VARCHAR(50) NOT NULL UNIQUE COMMENT 'Vertical',
      PRIMARY KEY (id)
    ) COMMENT 'Verticals';
CREATE TABLE partner_vertical_junction (
      primary_id INT(10) NOT NULL COMMENT 'Partner',
      lookup_id INT(10) NOT NULL COMMENT 'Vertical',
      CONSTRAINT pk PRIMARY KEY (primary_id, lookup_id),
      FOREIGN KEY (primary_id) REFERENCES partners(id),
      FOREIGN KEY (lookup_id) REFERENCES verticals(id)
    ) COMMENT 'Partner-vertical junction';
CREATE TABLE regions (
      id INT(10) NOT NULL AUTO_INCREMENT,
      name VARCHAR(50) NOT NULL UNIQUE COMMENT 'Region',
      PRIMARY KEY (id)
    ) COMMENT 'Regions';
CREATE TABLE partner_region_junction (
      primary_id INT(10) NOT NULL COMMENT 'Partner',
      lookup_id INT(10) NOT NULL COMMENT 'Region',
      CONSTRAINT pk PRIMARY KEY (primary_id, lookup_id),
      FOREIGN KEY (primary_id) REFERENCES partners(id),
      FOREIGN KEY (lookup_id) REFERENCES regions(id)
    ) COMMENT 'Partner-region junction';
CREATE TABLE consultants (
      id INT(10) NOT NULL AUTO_INCREMENT,
      name VARCHAR(10) COMMENT 'Name', /* FIXME: FOR NORMAL FORMS' SAKE. And make NOT NULL. */
      first_name VARCHAR(50) COMMENT 'First name',
      last_name VARCHAR(50) NOT NULL COMMENT 'Last name',
      rating_id int(10) COMMENT 'Overall rating', # FIXME: Added 6-6-16. Alter this?
      is_rejected BIT(1) NOT NULL COMMENT 'Rejected',
      PRIMARY KEY (id),
      FOREIGN KEY (rating_id) REFERENCES ratings(id)
    ) COMMENT 'Consultants';
CREATE TABLE consultant_skills (
      id INT(10) NOT NULL AUTO_INCREMENT,
      name VARCHAR(50) NOT NULL UNIQUE COMMENT 'Skill',
      PRIMARY KEY (id)
    ) COMMENT 'Consultant skills';
CREATE TABLE consultant_skill_ratings (
      primary_id INT(10) NOT NULL COMMENT 'Consultant',
      lookup_id INT(10) NOT NULL COMMENT 'Skill',
      rating_id INT(10) NOT NULL COMMENT 'Rating',
      CONSTRAINT pk PRIMARY KEY (primary_id, lookup_id),
      FOREIGN KEY (primary_id) REFERENCES consultants(id),
      FOREIGN KEY (lookup_id) REFERENCES consultant_skills(id),
      FOREIGN KEY (rating_id) REFERENCES ratings_simple(id)
    ) COMMENT 'Consultant ratings';
CREATE TABLE customers (
      id INT(10) NOT NULL AUTO_INCREMENT,
      name VARCHAR(50) NOT NULL COMMENT 'Name',
      website VARCHAR(50) COMMENT 'Website',
      notes VARCHAR(500) COMMENT 'Notes',
      PRIMARY KEY (id)
    ) COMMENT 'Customers';
CREATE TABLE opportunity_statuses (
      id INT(10) NOT NULL AUTO_INCREMENT,
      name VARCHAR(50) NOT NULL UNIQUE COMMENT 'Status',
      PRIMARY KEY (id)
    ) COMMENT 'Opportunity statuses';
CREATE TABLE opportunities (
      id INT(10) NOT NULL AUTO_INCREMENT,
      customer VARCHAR(50) NOT NULL COMMENT 'Customer', # FIXME: when customer table is implemented, change to: customer_id INT(10) NOT NULL,
      customer_rate FLOAT(15, 2) COMMENT 'Charge rate', # FIXME: Make DEFAULT 0?
      status_id INT(10) COMMENT 'Status', # FIXME: Make this NOT NULL?
      date_created DATE COMMENT 'Date created', # i.e. date this opp was opened/created (not the duration, which is stored in the junctions referencing this table)
      PRIMARY KEY (id),
      FOREIGN KEY (status_id) REFERENCES opportunity_statuses(id)
    ) COMMENT 'Opportunities';
CREATE TABLE opportunity_partner_junction (
      opportunity_id INT(10) NOT NULL COMMENT 'Opportunity',
      partner_id INT(10) NOT NULL COMMENT 'Partner',
      partner_rate FLOAT(15, 2) COMMENT 'Rate', # FIXME: Make DEFAULT 0?
      CONSTRAINT pk PRIMARY KEY (opportunity_id, partner_id),
      FOREIGN KEY (opportunity_id) REFERENCES opportunities(id),
      FOREIGN KEY (partner_id) REFERENCES partners(id)
    ) COMMENT = 'Opportunity-partner junction';
CREATE TABLE opportunity_consultant_junction (
      opportunity_id INT(10) NOT NULL COMMENT 'Opportunity',
      consultant_id INT(10) NOT NULL COMMENT 'Consultant',
      consultant_rate FLOAT(15, 2) COMMENT 'Rate', # FIXME: Make DEFAULT 0?
      CONSTRAINT pk PRIMARY KEY (opportunity_id, consultant_id),
      FOREIGN KEY (opportunity_id) REFERENCES opportunities(id),
      FOREIGN KEY (consultant_id) REFERENCES consultants(id)
    ) COMMENT = 'Opportunity-consultant junction';
CREATE TABLE activities (
      id INT(10) NOT NULL AUTO_INCREMENT,
      name VARCHAR(50) NOT NULL COMMENT 'Name',
      opportunity_id INT(10) NOT NULL COMMENT 'Opportunity',
      notes VARCHAR(500) COMMENT 'Notes',
      PRIMARY KEY (id),
      FOREIGN KEY (opportunity_id) REFERENCES opportunities(id)
    ) COMMENT 'Activities';
CREATE TABLE activity_technology_junction (
      primary_id INT(10) NOT NULL COMMENT 'Activity',
      lookup_id INT(10) NOT NULL COMMENT 'Technology',
      UNIQUE KEY (primary_id, lookup_id),
      FOREIGN KEY (primary_id) REFERENCES activities(id),
      FOREIGN KEY (lookup_id) REFERENCES technologies(id)
    ) COMMENT = 'Activity-technology junction';
CREATE TABLE activity_solution_junction (
      primary_id INT(10) NOT NULL COMMENT 'Activity',
      lookup_id INT(10) NOT NULL COMMENT 'Solution',
      UNIQUE KEY (primary_id, lookup_id),
      FOREIGN KEY (primary_id) REFERENCES activities(id),
      FOREIGN KEY (lookup_id) REFERENCES solutions(id)
    ) COMMENT = 'Activity-solution junction';
CREATE TABLE activity_misc_junction (
      primary_id INT(10) NOT NULL COMMENT 'Activity',
      lookup_id INT(10) NOT NULL COMMENT 'Miscellaneous',
      UNIQUE KEY (primary_id, lookup_id),
      FOREIGN KEY (primary_id) REFERENCES activities(id),
      FOREIGN KEY (lookup_id) REFERENCES misc(id)
    ) COMMENT = 'Activity-misc junction';
CREATE TABLE activity_partner_junction (
      activity_id INT(10) NOT NULL COMMENT 'Activity',
      partner_id INT(10) NOT NULL COMMENT 'Partner',
      UNIQUE KEY (activity_id, partner_id),
      FOREIGN KEY (activity_id) REFERENCES activities(id),
      FOREIGN KEY (partner_id) REFERENCES opportunity_partner_junction(partner_id)
    ) COMMENT = 'Activity-partner junction';
CREATE TABLE activity_consultant_junction (
      activity_id INT(10) NOT NULL COMMENT 'Activity',
      consultant_id INT(10) NOT NULL COMMENT 'Consultant',
      UNIQUE KEY (activity_id, consultant_id),
      FOREIGN KEY (activity_id) REFERENCES activities(id),
      FOREIGN KEY (consultant_id) REFERENCES opportunity_consultant_junction(consultant_id)
    ) COMMENT = 'Activity-consultant junction';
CREATE TABLE consultant_partner_junction (
      consultant_id INT(10) NOT NULL COMMENT 'Consultant',
      partner_id INT(10) NOT NULL COMMENT 'Partner',
      is_current BIT(1) COMMENT 'Currently employed', # FIXME: Adjust/reinterpret?
      CONSTRAINT pk PRIMARY KEY (consultant_id, partner_id),
      FOREIGN KEY (consultant_id) REFERENCES consultants(id),
      FOREIGN KEY (partner_id) REFERENCES partners(id)
    ) COMMENT = 'Consultant-partner junction';
