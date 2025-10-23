-- Seed data for development and testing

-- The scenario: Alice is picked up at the airport by Charlie and dropped off at downtown.
-- She then requests another ride back to the airport, which is yet to be assigned a driver.
-- Bob is currently on a ride from downtown to uptown with Charlie (who picked up Bob right after dropping off Alice).
-- The requested ride is expected to be picked up by David later (Eve is too far away and Frank is offline).

-- London Heathrow
\set AIRPORT_LOCATION 'ST_SetSRID(ST_MakePoint(-0.4542955, 51.4700223), 4326)::geography'

-- London Downtown
\set DOWNTOWN_LOCATION 'ST_SetSRID(ST_MakePoint(-0.1277583, 51.5073509), 4326)::geography'

-- London Downtown Alternate
\set DOWNTOWN_LOCATION_ALT 'ST_SetSRID(ST_MakePoint(-0.1297583, 51.5078509), 4326)::geography'

-- London Uptown
\set UPTOWN_LOCATION 'ST_SetSRID(ST_MakePoint(-0.141588, 51.515419), 4326)::geography'

-- London Midtown
\set MIDTOWN_LOCATION 'ST_SetSRID(ST_MakePoint(-0.1357, 51.5125), 4326)::geography'


INSERT INTO users (id, name, email) VALUES
('019a078d-e95e-7606-a2d8-b3dfa4bc1934', 'Alice', 'alice@example.com'),
('019a078d-e95e-78de-9df5-9b4a39281169', 'Bob', 'bob@example.com');

INSERT INTO drivers (id, name, email, current_location, status) VALUES
('019a078d-e95e-7981-914e-b5104cd166ee', 'Charlie', 'charlie@taxi.co.uk', :AIRPORT_LOCATION, 'busy'),
('019a078d-e95e-7e9f-8de1-e6d25c4dcbd4', 'David', 'david@taxi.co.uk', :DOWNTOWN_LOCATION, 'available'),
('019a078d-e95e-75c1-ac7e-6121da5520ed', 'Eve', 'eve@taxi.co.uk', :DOWNTOWN_LOCATION_ALT, 'available'),
('019a078d-e95e-7dba-be51-b1e4d16ff8a8', 'Frank', 'frank@taxi.co.uk', :DOWNTOWN_LOCATION, 'offline');

-- INSERT INTO rides (id, user_id, departure_location, destination_location, driver_id, state, created_at) VALUES
-- ('019a078d-e95e-70b6-8b6d-d3b80bf115cf', '019a078d-e95e-7606-a2d8-b3dfa4bc1934', :AIRPORT_LOCATION, :DOWNTOWN_LOCATION, '019a078d-e95e-7981-914e-b5104cd166ee', 'completed', '2025-10-21 10:00:00+01:00'),
-- ('019a078d-e95e-757e-8cdb-abe4223ee2a9', '019a078d-e95e-78de-9df5-9b4a39281169', :DOWNTOWN_LOCATION, :UPTOWN_LOCATION, '019a078d-e95e-7981-914e-b5104cd166ee', 'in_progress', '2025-10-21 10:04:00+01:00'),
-- ('019a078d-e95e-7752-8d80-748cb0a901e9', '019a078d-e95e-7606-a2d8-b3dfa4bc1934', :DOWNTOWN_LOCATION, :AIRPORT_LOCATION, NULL, 'requested', '2025-10-21 10:35:00+01:00');