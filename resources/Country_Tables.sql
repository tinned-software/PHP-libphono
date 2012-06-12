-- 
-- 
-- SQLLite
-- 

CREATE TABLE Country_Codes (id  INTEGER NOT NULL, country_3_letter varchar(3) NOT NULL, country_2_letter varchar(2), PRIMARY KEY (id));
CREATE UNIQUE INDEX Country_Codes_id ON Country_Codes (id);
CREATE UNIQUE INDEX Country_Codes_country_3_letter ON Country_Codes (country_3_letter);
CREATE INDEX Country_Codes_country_2_letter ON Country_Codes (country_2_letter);

CREATE TABLE Country_Dialcodes (id  INTEGER NOT NULL, country_3_letter varchar(3) NOT NULL, international_dialcode varchar(10) NOT NULL, extended_dialcode varchar(10) NOT NULL UNIQUE, PRIMARY KEY (id));
CREATE UNIQUE INDEX Country_Dialcodes_id ON Country_Dialcodes (id);
CREATE INDEX Country_Dialcodes_country_3_letter ON Country_Dialcodes (country_3_letter);
CREATE INDEX Country_Dialcodes_international_dialcode ON Country_Dialcodes (international_dialcode);

CREATE TABLE Country_Exit_Dialcode (id  INTEGER NOT NULL, country_3_letter varchar(3) NOT NULL, exit_dialcode varchar(10) NOT NULL, PRIMARY KEY (id));
CREATE UNIQUE INDEX Country_Exit_Dialcode_id ON Country_Exit_Dialcode (id);
CREATE INDEX Country_Exit_Dialcode_country_3_letter ON Country_Exit_Dialcode (country_3_letter);

CREATE TABLE Country_Names (id  INTEGER NOT NULL, country_3_letter varchar(3) NOT NULL, language_3_letter varchar(3) NOT NULL, country_name varchar(255) NOT NULL, PRIMARY KEY (id));
CREATE UNIQUE INDEX Country_Names_id ON Country_Names (id);
CREATE INDEX Country_Names_country_3_letter ON Country_Names (country_3_letter);

CREATE TABLE Country_Trunk_Code (id  INTEGER NOT NULL, country_3_letter varchar(3) NOT NULL, trunk_dialcode char(5), PRIMARY KEY (id));
CREATE UNIQUE INDEX Country_Trunk_Code_id ON Country_Trunk_Code (id);
CREATE INDEX Country_Trunk_Code_country_3_letter ON Country_Trunk_Code (country_3_letter);

/*
CREATE VIEW Country_Dialplan AS SELECT Country_Codes.country_3_letter, 
    Country_Dialcodes.international_dialcode, 
    Country_Dialcodes.extended_dialcode,
    Country_Exit_Dialcode.exit_dialcode,
    Country_Names.language_3_letter,
    Country_Names.country_name
FROM Country_Codes 
INNER JOIN Country_Dialcodes ON Country_Codes.country_3_letter = Country_Dialcodes.country_3_letter
INNER JOIN Country_Exit_Dialcode ON Country_Codes.country_3_letter = Country_exit_dialcode.country_3_letter
INNER JOIN Country_Names ON Country_Codes.country_3_letter = Country_Names.country_3_letter
;
*/
