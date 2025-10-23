CREATE TABLE drivers (
    id UUID PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    -- Using PostGIS extension for geospatial data
    -- GEOGRAPHY is standard for distance calculations on a globe
    current_location GEOGRAPHY(Point, 4326) NOT NULL,
    status VARCHAR(20) NOT NULL,
    updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

-- Using GiST Index for fast proximity search
CREATE INDEX idx_drivers_location ON drivers USING GIST (current_location);
CREATE INDEX idx_drivers_status ON drivers (status);
