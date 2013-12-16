<?php
namespace Scalr\Upgrade\Updates;

use Scalr\Upgrade\SequenceInterface;
use Scalr\Upgrade\UpdateInterface;
use Scalr\Upgrade\AbstractUpdate;

class Update20131212214901 extends AbstractUpdate implements SequenceInterface
{
    /**
     * A UUID is a 16-octet (128-bit) number.
     * UUID is represented by 32 hexadecimal digits, displayed in five groups separated by hyphens,
     * in the form 8-4-4-4-12 for a total of 36 characters (32 alphanumeric characters and four hyphens)
     *
     * For example: 270e9300-e83f-7283-a716-346edd410780
     *
     * @var string
     */
    protected $uuid = '8cd24661-9b31-4056-89e4-465d0f07f4dd';

    /**
     * The list of identifiers of updates this upgrade depends upon.
     * Current upgrade will perform only if all dependant upgrades have already been applied.
     *
     * For example:
     * array(
     *     '270e9300-e83f-7283-a716-346edd410780',
     *     '23dede00-e783-8893-ebba-176867838640'
     * );
     *
     * @var array
     */
    protected $depends = array();

    /**
     * A short description of the upgrade
     *
     * @var string
     */
    protected $description = 'Cleanup ELB settings and make sure that ELB usage db is consistent';

    /**
     * The source type of upgrade.
     * Can be either mysql or filesystem.
     * Default value: UpdateInterface::TYPE_MYSQL
     *
     * @var string
     */
    protected $type = UpdateInterface::TYPE_MYSQL;


	/**
     * {@inheritdoc}
     * @see Scalr\Upgrade.SequenceInterface::getNumberStages()
     */
    public function getNumberStages()
    {
        //TODO provide the number of consecutive stages
        return 1;
    }

    /**
     * Checks whether the update of the stage ONE is applied.
     *
     * Verifies whether current update has already been applied to this install.
     * This ensures avoiding the duplications. Implementation of this method should give
     * the definite answer to question "has been this update applied or not?".
     *
     * @param   int  $stage  optional The stage number
     * @return  bool Returns true if the update has already been applied.
     */
	protected function isApplied1($stage)
    {
        return false;
    }

    /**
     * Validates an environment before it will try to apply the update of the stage ONE.
     *
     * Validates current environment or inspects circumstances that is expected to be in the certain state
     * before the update is applied. This method may not be overridden from AbstractUpdate class
     * which means current update is always valid.
     *
     * @param   int  $stage  optional The stage number
     * @return  bool Returns true if the environment meets the requirements.
     */
	protected function validateBefore1($stage)
    {
        return true;
    }

	/**
     * Performs upgrade literally for the stage ONE.
     *
     * Implementation of this method performs update steps needs to be taken
     * to accomplish upgrade successfully.
     *
     * If there are any error during an execution of this scenario it must
     * throw an exception.
     *
     * @param   int  $stage  optional The stage number
     * @throws  \Exception
     */
    protected function run1($stage)
    {
        $this->db->Execute("DELETE FROM farm_role_cloud_services WHERE id NOT IN (SELECT value FROM farm_role_settings WHERE name='aws.elb.id')");

        $elbs = $this->db->Execute("SELECT * from farm_role_settings WHERE name='aws.elb.id'");
        while ($elb = $elbs->FetchRow()) {
            $service = $this->db->GetRow("SELECT * FROM farm_role_cloud_services WHERE id = ?", array($elb['value']));
            if ($service['farm_role_id'] != $elb['farm_roleid']) {
                $this->db->Execute("UPDATE farm_role_cloud_services SET
                   farm_role_id = ?,
                   farm_id =?
                WHERE id = ?
                ", array(
                    $elb['farm_roleid'],
                    $this->db->GetOne("SELECT farmid FROM farm_roles WHERE id = ?", array($elb['farm_roleid'])),
                    $elb['value']
                ));
            }
        }
    }
}