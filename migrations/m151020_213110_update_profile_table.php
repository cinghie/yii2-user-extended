<?php

/**
 * @copyright Copyright &copy; Gogodigital Srls
 * @company Gogodigital Srls - Wide ICT Solutions
 * @website http://www.gogodigital.it
 * @github https://github.com/cinghie/yii2-user-extended
 * @license GNU GENERAL PUBLIC LICENSE VERSION 3
 * @package yii2-user-extended
 * @version 0.6.2
 */

use cinghie\traits\migrations\Migration;

/**
 * Class m151020_213110_update_profile_table
 */
class m151020_213110_update_profile_table extends Migration
{
	/**
	 * @inheritdoc
	 */
    public function up()
    {
	    $this->addColumn('{{%profile}}', 'signature', $this->text()->after('bio'));
    }

	/**
	 * @inheritdoc
	 */
    public function down()
    {
        $this->dropColumn('{{%profile}}', 'signature');
    }
}
