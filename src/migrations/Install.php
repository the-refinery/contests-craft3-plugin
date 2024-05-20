<?php
/**
 * CraftCMS Contests plugin for Craft CMS 4.x
 *
 * This is a plugin that allows you to run contests with voting in your CraftCMS site
 *
 * @link      https://the-refinery.io
 * @copyright Copyright (c) 2021 The Refinery
 */

namespace therefinery\craftcmscontests\migrations;

use therefinery\craftcmscontests\CraftcmsContests;

use Craft;
use craft\config\DbConfig;
use craft\db\Migration;

/**
 * @author    The Refinery
 * @package   CraftcmsContests
 * @since     1.0.0
 */
class Install extends Migration
{
    // Public Properties
    // =========================================================================

    /**
     * @var string The database driver to use
     */
    public string $driver;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp(): mixed
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        if ($this->createTables()) {
            $this->createIndexes();
            $this->addForeignKeys();
            // Refresh the db schema caches
            Craft::$app->db->schema->refresh();
            $this->insertDefaultData();
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): mixed
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        $this->removeTables();

        return true;
    }

    // Protected Methods
    // =========================================================================

    /**
     * @return bool
     */
    protected function createTables(): bool
    {
        $tablesCreated = false;

        $tableSchema = Craft::$app->db->schema->getTableSchema(
            "{{%craftcms_contests}}",
        );
        if ($tableSchema === null) {
            $tablesCreated = true;
            $this->createTable("{{%craftcms_contests}}", [
                "id" => $this->primaryKey(),
                "uid" => $this->uid(),
                "dateCreated" => $this->dateTime()->notNull(),
                "dateUpdated" => $this->dateTime()->notNull(),
                "name" => $this->string(255)->notNull(),
                "handle" => $this->string()->notNull(),
                "categories" => $this->text()->null(),
                "dateStart" => $this->dateTime()->null(),
                "dateEnd" => $this->dateTime()->null(),
                "lockoutLength" => $this->integer()
                    ->defaultValue(24)
                    ->null(),
                "lockoutFrequency" => $this->string(64)
                    ->defaultValue("hour")
                    ->null(),
                "enabled" => $this->boolean()
                    ->defaultValue(true)
                    ->null(),
                "sessionProtect" => $this->boolean()
                    ->defaultValue(true)
                    ->null(),
                "recaptchaSecret" => $this->text()->null(),
            ]);
        }

        $tableSchema = Craft::$app->db->schema->getTableSchema(
            "{{%craftcms_contests_votes}}",
        );
        if ($tableSchema === null) {
            $tablesCreated = true;
            $this->createTable("{{%craftcms_contests_votes}}", [
                "id" => $this->primaryKey(),
                "dateCreated" => $this->dateTime()->notNull(),
                "dateUpdated" => $this->dateTime()->notNull(),
                "uid" => $this->uid(),
                "contestId" => $this->integer()->null(),
                "categoryId" => $this->integer()->null(),
                "entryId" => $this->integer()->null(),
                "email" => $this->string(255)->null(),
                "ip" => $this->string(255)->null(),
                "extraData" => $this->text()->null(),
                "userAgent" => $this->string(512)->null(),
            ]);
        }

        return $tablesCreated;
    }

    /**
     * @return void
     */
    protected function createIndexes(): void
    {
        $this->createIndex(null, "{{%craftcms_contests}}", ["name"], true);
        $this->createIndex(null, "{{%craftcms_contests}}", ["handle"], true);

        $this->createIndex(
            null,
            "{{%craftcms_contests_votes}}",
            ["email"],
            false,
        );
        $this->createIndex(
            null,
            "{{%craftcms_contests_votes}}",
            ["email", "categoryId"],
            false,
        );
    }

    /**
     * @return void
     */
    protected function addForeignKeys(): void
    {
        $this->addForeignKey(
            null,
            "{{%craftcms_contests_votes}}",
            ["contestId"],
            "{{%craftcms_contests}}",
            ["id"],
            "CASCADE",
            null,
        );
    }

    /**
     * @return void
     */
    protected function insertDefaultData(): void
    {
    }

    /**
     * @return void
     */
    protected function removeTables(): void
    {
        $this->dropTableIfExists("{{%craftcms_contests_votes}}");
        $this->dropTableIfExists("{{%craftcms_contests}}");
    }
}

/*

CraftCMS v2 DATA, keeping here for reference.
mysql> describe craft_contestify_votes;
+-------------+--------------+------+-----+---------+----------------+
| Field       | Type         | Null | Key | Default | Extra          |
+-------------+--------------+------+-----+---------+----------------+
| id          | int(11)      | NO   | PRI | NULL    | auto_increment |
| contestId   | int(10)      | YES  | MUL | NULL    |                |
| categoryId  | int(10)      | YES  |     | NULL    |                |
| entryId     | int(10)      | YES  |     | NULL    |                |
| email       | varchar(255) | YES  | MUL | NULL    |                |
| ip          | varchar(255) | YES  |     | NULL    |                |
| extraData   | text         | YES  |     | NULL    |                |
| userAgent   | varchar(512) | YES  |     | NULL    |                |
| dateCreated | datetime     | NO   |     | NULL    |                |
| dateUpdated | datetime     | NO   |     | NULL    |                |
| uid         | char(36)     | NO   |     | 0       |                |
+-------------+--------------+------+-----+---------+----------------+

mysql> show index from craft_contestify_votes;
+------------------------+------------+---------------------------------------------+--------------+-------------+-----------+-------------+----------+--------+------+------------+---------+---------------+
| Table                  | Non_unique | Key_name                                    | Seq_in_index | Column_name | Collation | Cardinality | Sub_part | Packed | Null | Index_type | Comment | Index_comment |
+------------------------+------------+---------------------------------------------+--------------+-------------+-----------+-------------+----------+--------+------+------------+---------+---------------+
| craft_contestify_votes |          0 | PRIMARY                                     |            1 | id          | A         |       31973 |     NULL | NULL   |      | BTREE      |         |               |
| craft_contestify_votes |          1 | craft_contestify_votes_contestId_fk         |            1 | contestId   | A         |           2 |     NULL | NULL   | YES  | BTREE      |         |               |
| craft_contestify_votes |          1 | craft_contestify_votes_email_idx            |            1 | email       | A         |       16487 |     NULL | NULL   | YES  | BTREE      |         |               |
| craft_contestify_votes |          1 | craft_contestify_votes_email_categoryId_idx |            1 | email       | A         |       17028 |     NULL | NULL   | YES  | BTREE      |         |               |
| craft_contestify_votes |          1 | craft_contestify_votes_email_categoryId_idx |            2 | categoryId  | A         |       16624 |     NULL | NULL   | YES  | BTREE      |         |               |
+------------------------+------------+---------------------------------------------+--------------+-------------+-----------+-------------+----------+--------+------+------------+---------+---------------+

mysql> describe craft_contestify_contests;
+------------------+--------------+------+-----+---------+----------------+
| Field            | Type         | Null | Key | Default | Extra          |
+------------------+--------------+------+-----+---------+----------------+
| id               | int(11)      | NO   | PRI | NULL    | auto_increment |
| name             | varchar(255) | NO   | UNI | NULL    |                |
| handle           | varchar(255) | NO   | UNI | NULL    |                |
| categories       | text         | YES  |     | NULL    |                |
| dateStart        | datetime     | YES  |     | NULL    |                |
| dateEnd          | datetime     | YES  |     | NULL    |                |
| lockoutLength    | int(11)      | YES  |     | 24      |                |
| lockoutFrequency | varchar(64)  | YES  |     | hour    |                |
| enabled          | tinyint(1)   | YES  |     | 1       |                |
| sessionProtect   | tinyint(1)   | YES  |     | 1       |                |
| recaptchaSecret  | text         | YES  |     | NULL    |                |
| dateCreated      | datetime     | NO   |     | NULL    |                |
| dateUpdated      | datetime     | NO   |     | NULL    |                |
| uid              | char(36)     | NO   |     | 0       |                |
+------------------+--------------+------+-----+---------+----------------+

mysql> show index from craft_contestify_contests;
+---------------------------+------------+------------------------------------------+--------------+-------------+-----------+-------------+----------+--------+------+------------+---------+---------------+
| Table                     | Non_unique | Key_name                                 | Seq_in_index | Column_name | Collation | Cardinality | Sub_part | Packed | Null | Index_type | Comment | Index_comment |
+---------------------------+------------+------------------------------------------+--------------+-------------+-----------+-------------+----------+--------+------+------------+---------+---------------+
| craft_contestify_contests |          0 | PRIMARY                                  |            1 | id          | A         |           2 |     NULL | NULL   |      | BTREE      |         |               |
| craft_contestify_contests |          0 | craft_contestify_contests_id_unq_idx     |            1 | id          | A         |           2 |     NULL | NULL   |      | BTREE      |         |               |
| craft_contestify_contests |          0 | craft_contestify_contests_name_unq_idx   |            1 | name        | A         |           2 |     NULL | NULL   |      | BTREE      |         |               |
| craft_contestify_contests |          0 | craft_contestify_contests_handle_unq_idx |            1 | handle      | A         |           2 |     NULL | NULL   |      | BTREE      |         |               |
+---------------------------+------------+------------------------------------------+--------------+-------------+-----------+-------------+----------+--------+------+------------+---------+---------------+
*/
