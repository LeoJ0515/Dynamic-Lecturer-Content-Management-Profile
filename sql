-- 1. USERS TABLE (for login)
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL, -- Plain text password
    created_at TIMESTAMP DEFAULT NOW()
);

-- 2. PROFILE TABLE (main info + images)
CREATE TABLE profile (
    id SERIAL PRIMARY KEY,
    full_name VARCHAR(200),
    designation VARCHAR(200),
    department VARCHAR(200),
    institution VARCHAR(200),
    email VARCHAR(255),
    contact VARCHAR(50),
    bio TEXT,
    profile_picture TEXT,
    background_picture TEXT,
    cv_file TEXT,
    updated_at TIMESTAMP DEFAULT NOW()
);

-- 3. AWARDS TABLE
CREATE TABLE awards (
    id SERIAL PRIMARY KEY,
    title VARCHAR(300),
    organization VARCHAR(300),
    year INTEGER,
    description TEXT,
    display_order INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT NOW()
);

-- 4. APPOINTMENTS TABLE
CREATE TABLE appointments (
    id SERIAL PRIMARY KEY,
    position VARCHAR(300),
    organization VARCHAR(300),
    start_year INTEGER,
    end_year INTEGER,
    is_current BOOLEAN DEFAULT false,
    description TEXT,
    display_order INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT NOW()
);

-- 5. INVITED_TALKS TABLE
CREATE TABLE invited_talks (
    id SERIAL PRIMARY KEY,
    event_name VARCHAR(300),
    talk_title VARCHAR(300),
    venue VARCHAR(300),
    year INTEGER,
    description TEXT,
    display_order INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT NOW()
);

-- 6. RESEARCH_AREAS TABLE
CREATE TABLE research_areas (
    id SERIAL PRIMARY KEY,
    area_name VARCHAR(200),
    display_order INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT NOW()
);

-- 7. PUBLICATIONS TABLE
CREATE TABLE publications (
    id SERIAL PRIMARY KEY,
    title VARCHAR(500),
    authors TEXT,
    journal VARCHAR(300),
    year INTEGER,
    volume VARCHAR(50),
    issue VARCHAR(50),
    pages VARCHAR(50),
    doi VARCHAR(100),
    url TEXT,
    type VARCHAR(50),
    display_order INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT NOW()
);

-- 8. SUPERVISION TABLE
CREATE TABLE IF NOT EXISTS supervision (
    id SERIAL PRIMARY KEY,
    student_name VARCHAR(200),
    thesis_title VARCHAR(500),
    program VARCHAR(100),
    role VARCHAR(100),
    start_year INTEGER,
    completion_year INTEGER,
    status VARCHAR(50),
    degree TEXT,
    display_order INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT NOW()
);

-- 9. TEACHING TABLE
CREATE TABLE teaching (
    id SERIAL PRIMARY KEY,
    course_code VARCHAR(50),
    course_name VARCHAR(300),
    institution VARCHAR(300),
    semester VARCHAR(50),
    year INTEGER,
    description TEXT,
    display_order INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS research_projects (
    id BIGSERIAL PRIMARY KEY,
    project_title TEXT NOT NULL,
    type_of_grant TEXT,
    funding_body TEXT,
    start_year INTEGER,
    end_year INTEGER,
    status TEXT DEFAULT 'Ongoing',
    description TEXT,
    display_order INTEGER DEFAULT 0,
    created_at TIMESTAMPTZ DEFAULT NOW()
);

INSERT INTO users (email, password) 
VALUES ('Your_Email', 'Your_Password');
