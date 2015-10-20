<?php

/**
 * @copyright Copyright &copy; Gogodigital Srls
 * @company Gogodigital Srls - Wide ICT Solutions
 * @website http://www.gogodigital.it
 * @github https://github.com/cinghie/yii2-user-extended
 * @license GNU GENERAL PUBLIC LICENSE VERSION 3
 * @package yii2-user-extended
 * @version 0.4.0
 */

use yii\db\Schema;
use yii\db\Migration;

class m151020_213100_update_profile_table extends Migration
{
    
    public function up()
    {
        $this->addColumn('{{%profile}}', 'firstname', Schema::TYPE_STRING. ' DEFAULT NULL');
        $this->addColumn('{{%profile}}', 'lastname', Schema::TYPE_STRING. ' DEFAULT NULL');
        $this->addColumn('{{%profile}}', 'birthday', Schema::TYPE_DATE. ' NOT NULL');
        $this->addColumn('{{%profile}}', 'avatar', Schema::TYPE_STRING. ' NOT NULL');
        $this->addColumn('{{%profile}}', 'terms', 'tinyint(1) NOT NULL DEFAULT 0');
    }

    public function down()
    {
        $this->dropColumn('{{%profile}}', 'firstname');
        $this->dropColumn('{{%profile}}', 'lastname');
        $this->dropColumn('{{%profile}}', 'birthday');
        $this->dropColumn('{{%profile}}', 'avatar');
        $this->dropColumn('{{%profile}}', 'terms');
    }

}
