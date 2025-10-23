CREATE TABLE command_queue (
    id UUID PRIMARY KEY,
    status VARCHAR(20) NOT NULL,
    type VARCHAR(255) NOT NULL,
    payload TEXT NOT NULL,
    attempts INT NOT NULL DEFAULT 0,
    last_error TEXT,
    created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_command_queue_status_created_at ON command_queue(status, created_at);
CREATE INDEX idx_command_queue_type ON command_queue(type);
