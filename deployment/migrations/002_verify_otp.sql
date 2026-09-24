-- Run on databases created before verify-otp support. Existing operators
-- remain unset until an admin saves their verify-otp token.
ALTER TABLE uc_operators ADD COLUMN IF NOT EXISTS verify_otp_encrypted TEXT NULL AFTER otp_encrypted;