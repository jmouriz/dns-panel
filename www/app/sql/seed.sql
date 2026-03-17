INSERT INTO roles(name, description) VALUES
('administrator', 'Full access'),
('hostmaster', 'Zone manager'),
('viewer', 'Read only');

INSERT INTO permissions(code, description) VALUES
('zones.view','View zones'),
('zones.create','Create zones'),
('zones.delete','Delete zones'),
('records.view','View records'),
('records.edit','Edit records'),
('soa.edit','Edit SOA'),
('dnssec.view','View DNSSEC'),
('dnssec.enable','Enable DNSSEC'),
('dnssec.disable','Disable DNSSEC'),
('users.manage','Manage users'),
('roles.manage','Manage roles'),
('config.manage','Manage configuration');

INSERT INTO role_permissions(role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p WHERE r.name='administrator';

INSERT INTO role_permissions(role_id, permission_id)
SELECT r.id, p.id
FROM roles r JOIN permissions p ON p.code IN (
  'zones.view','records.view','records.edit','soa.edit','dnssec.view','dnssec.enable','dnssec.disable'
)
WHERE r.name='hostmaster';

INSERT INTO role_permissions(role_id, permission_id)
SELECT r.id, p.id
FROM roles r JOIN permissions p ON p.code IN (
  'zones.view','records.view','dnssec.view'
)
WHERE r.name='viewer';
