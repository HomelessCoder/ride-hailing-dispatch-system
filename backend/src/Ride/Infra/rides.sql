CREATE TABLE rides (
    id UUID PRIMARY KEY,
    user_id UUID NOT NULL REFERENCES users(id),
    departure_location GEOGRAPHY(Point, 4326) NOT NULL,
    destination_location GEOGRAPHY(Point, 4326) NOT NULL,
    driver_id UUID REFERENCES drivers(id),
    state VARCHAR(20) NOT NULL,
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_rides_state ON rides (state);
