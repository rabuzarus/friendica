Table photo
===========

| Field       | Description                                            | Type             | Null | Key | Default             | Extra           |
| ----------- | ------------------------------------------------------ | ---------------- | ---- | --- | ------------------- | --------------- |
| id          | sequential ID                                          | int(10) unsigned | NO   | PRI | NULL                | auto_increment  |
| uid         | user.id of the owner of this data                      | int(10) unsigned | NO   | MUL | 0                   |                 |
| contact-id  | contact.id                                             | int(10) unsigned | NO   |     | 0                   |                 |
| guid        | A unique identifier for this photo                     | varchar(64)      | NO   | MUL |                     |                 |
| resource-id |                                                        | varchar(255)     | NO   | MUL |                     |                 |
| created     | creation date                                          | datetime         | NO   |     | 0001-01-01 00:00:00 |                 |
| edited      | last edited date                                       | datetime         | NO   |     | 0001-01-01 00:00:00 |                 |
| title       |                                                        | varchar(255)     | NO   |     |                     |                 |
| desc        |                                                        | text             | NO   |     | NULL                |                 |
| album       | The name of the album to which the photo belongs       | varchar(255)     | NO   |     |                     |                 |
| filename    |                                                        | varchar(255)     | NO   |     |                     |                 |
| type        |  image type                                            | varchar(128)     | NO   |     | image/jpeg          |                 |
| height      |                                                        | smallint(6)      | NO   |     | 0                   |                 |
| width       |                                                        | smallint(6)      | NO   |     | 0                   |                 |
| size        |                                                        | int(10) unsigned | NO   |     | 0                   |                 |
| data        |                                                        | mediumblob       | NO   |     | NULL                |                 |
| scale       |                                                        | tinyint(3)       | NO   |     | 0                   |                 |
| photo_usage | Usage Type of the photo                                | smallint(6)      | NO   |     | 0                   |                 |
| profile     |                                                        | tinyint(1)       | NO   |     | 0                   |                 |
| allow_cid   | Access Control - list of allowed contact.id '<19><78>' | mediumtext       | NO   |     | NULL                |                 |
| allow_gid   | Access Control - list of allowed groups                | mediumtext       | NO   |     | NULL                |                 |
| deny_cid    | Access Control - list of denied contact.id             | mediumtext       | NO   |     | NULL                |                 |
| deny_gid    | Access Control - list of denied groups                 | mediumtext       | NO   |     | NULL                |                 |

```
/**
 * @name Photo usage types
 * 
 * Different types of photo usage
 * 
 */
define ( 'PHOTO_NORMAL',           0x0000 );
define ( 'PHOTO_PROFILE',          0x0001 );
define ( 'PHOTO_CONTACT',          0x0002 );
define ( 'PHOTO_COVER',            0x0004 );
define ( 'PHOTO_CACHE',            0x0064 );
```

Return to [database documentation](help/database)
