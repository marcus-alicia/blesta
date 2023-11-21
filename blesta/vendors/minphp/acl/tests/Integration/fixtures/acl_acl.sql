CREATE TABLE "acl_acl" (
"aro_id" INTEGER NOT NULL ,
"aco_id" INTEGER NOT NULL ,
"action" VARCHAR( 255 ) NOT NULL ,
"permission" VARCHAR( 16 )  NOT NULL ,
PRIMARY KEY ( "aro_id" , "aco_id" , "action" )
);