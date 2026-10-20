<?php

declare(strict_types=1);

/**
 * Add registration-profile columns on personnel if they are not there yet.
 * Safe to run more than once.
 */
require dirname(__DIR__) . '/config/bootstrap.php';

$exists = db()->fetch("SHOW COLUMNS FROM personnel LIKE 'member_type'");
if ($exists) {
    echo "personnel profile columns already exist\n";
    exit(0);
}

$statements = [
    "ALTER TABLE personnel
        ADD COLUMN member_type ENUM('staff','student','general') NOT NULL DEFAULT 'staff' AFTER title,
        ADD COLUMN personal_type_id INT UNSIGNED NULL DEFAULT NULL AFTER member_type,
        ADD COLUMN position_text VARCHAR(120) NULL DEFAULT NULL AFTER personal_type_id,
        ADD COLUMN org_unit_id INT UNSIGNED NULL DEFAULT NULL AFTER position_text,
        ADD COLUMN area_id INT UNSIGNED NULL DEFAULT NULL AFTER org_unit_id,
        ADD COLUMN cluster_code VARCHAR(10) NULL DEFAULT NULL AFTER area_id,
        ADD COLUMN school_code VARCHAR(40) NULL DEFAULT NULL AFTER cluster_code,
        ADD COLUMN org_name VARCHAR(255) NULL DEFAULT NULL AFTER school_code,
        ADD COLUMN affiliation VARCHAR(255) NULL DEFAULT NULL AFTER org_name,
        ADD COLUMN province_id INT UNSIGNED NULL DEFAULT NULL AFTER affiliation,
        ADD COLUMN amphur_id INT UNSIGNED NULL DEFAULT NULL AFTER province_id,
        ADD COLUMN tumbon_id INT UNSIGNED NULL DEFAULT NULL AFTER amphur_id,
        ADD COLUMN zipcode VARCHAR(10) NULL DEFAULT NULL AFTER tumbon_id",
    'ALTER TABLE personnel ADD KEY idx_personnel_member_type (member_type)',
    'ALTER TABLE personnel ADD KEY idx_personnel_province (province_id)',
    "UPDATE personnel p
        INNER JOIN users u ON u.id = p.user_id AND u.deleted_at IS NULL
        SET p.member_type = u.member_type,
            p.personal_type_id = u.personal_type_id,
            p.position_text = u.position_text,
            p.org_unit_id = u.org_unit_id,
            p.area_id = u.area_id,
            p.cluster_code = u.cluster_code,
            p.school_code = u.school_code,
            p.org_name = u.org_name,
            p.affiliation = u.affiliation,
            p.province_id = u.province_id,
            p.amphur_id = u.amphur_id,
            p.tumbon_id = u.tumbon_id,
            p.zipcode = u.zipcode",
];

foreach ($statements as $sql) {
    db()->query($sql);
}

echo "personnel profile columns added\n";
