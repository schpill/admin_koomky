-- Enable UUID extension
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- Enable trigram extension for fallback search
CREATE EXTENSION IF NOT EXISTS "pg_trgm";

-- Create function to auto-generate UUID
CREATE OR REPLACE FUNCTION public.uuid_generate_v4() RETURNS UUID AS $$
  SELECT uuid-ossp_uuid_generate_v4()
$$ LANGUAGE SQL;
