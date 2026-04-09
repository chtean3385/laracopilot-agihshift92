--
-- PostgreSQL database dump
--

\restrict Ums4SncGNqaFhiFZQDCwY2eXRRWRfp6FAQlWuueJZAs1JGhU1YcRGo00vdFwBpv

-- Dumped from database version 16.10
-- Dumped by pg_dump version 16.10

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: activity_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.activity_logs (
    id bigint NOT NULL,
    user_name character varying(191) NOT NULL,
    user_email character varying(191) NOT NULL,
    user_role character varying(191) NOT NULL,
    action character varying(191) NOT NULL,
    module character varying(191) NOT NULL,
    description text NOT NULL,
    ip_address character varying(191),
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    hotel_id bigint
);


--
-- Name: activity_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.activity_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: activity_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.activity_logs_id_seq OWNED BY public.activity_logs.id;


--
-- Name: booking_add_ons; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.booking_add_ons (
    id bigint NOT NULL,
    booking_id bigint NOT NULL,
    add_on_id bigint,
    name character varying(191) NOT NULL,
    price numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: booking_add_ons_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.booking_add_ons_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: booking_add_ons_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.booking_add_ons_id_seq OWNED BY public.booking_add_ons.id;


--
-- Name: booking_extra_charges; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.booking_extra_charges (
    id bigint NOT NULL,
    booking_id bigint NOT NULL,
    name character varying(191) NOT NULL,
    category character varying(50) DEFAULT 'other'::character varying NOT NULL,
    quantity numeric(8,2) DEFAULT '1'::numeric NOT NULL,
    unit_price numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    total_price numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    notes text,
    added_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: booking_extra_charges_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.booking_extra_charges_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: booking_extra_charges_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.booking_extra_charges_id_seq OWNED BY public.booking_extra_charges.id;


--
-- Name: booking_guests; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.booking_guests (
    id bigint NOT NULL,
    booking_id bigint NOT NULL,
    name character varying(191) NOT NULL,
    age integer,
    gender character varying(255),
    nationality character varying(191) DEFAULT 'Indian'::character varying NOT NULL,
    id_type character varying(191),
    id_number character varying(191),
    dob date,
    relation character varying(191),
    signature text,
    id_document_path character varying(191),
    id_document_name character varying(191),
    notes character varying(191),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT booking_guests_gender_check CHECK (((gender)::text = ANY ((ARRAY['male'::character varying, 'female'::character varying, 'other'::character varying])::text[])))
);


--
-- Name: booking_guests_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.booking_guests_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: booking_guests_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.booking_guests_id_seq OWNED BY public.booking_guests.id;


--
-- Name: bookings; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.bookings (
    id bigint NOT NULL,
    booking_number character varying(191) NOT NULL,
    customer_id bigint NOT NULL,
    room_id bigint NOT NULL,
    check_in_date date NOT NULL,
    check_out_date date NOT NULL,
    actual_checkin_at timestamp(0) without time zone,
    actual_checkout_at timestamp(0) without time zone,
    nights integer DEFAULT 1 NOT NULL,
    adults integer DEFAULT 1 NOT NULL,
    children integer DEFAULT 0 NOT NULL,
    total_amount numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    advance_payment numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    balance_due numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    special_requests text,
    status character varying(191) DEFAULT 'confirmed'::character varying NOT NULL,
    payment_status character varying(191) DEFAULT 'pending'::character varying NOT NULL,
    checkin_notes text,
    checkout_notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    meal_breakfast boolean DEFAULT false NOT NULL,
    meal_lunch boolean DEFAULT false NOT NULL,
    meal_dinner boolean DEFAULT false NOT NULL,
    meal_cost numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    extra_beds smallint DEFAULT '0'::smallint NOT NULL,
    extra_bed_cost numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    hotel_id bigint,
    time_slot_id bigint,
    booking_date date,
    slot_start_time character varying(5),
    slot_end_time character varying(5),
    hours_booked smallint
);


--
-- Name: bookings_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.bookings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: bookings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.bookings_id_seq OWNED BY public.bookings.id;


--
-- Name: cache; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache (
    key character varying(191) NOT NULL,
    value text NOT NULL,
    expiration integer NOT NULL
);


--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache_locks (
    key character varying(191) NOT NULL,
    owner character varying(191) NOT NULL,
    expiration integer NOT NULL
);


--
-- Name: channel_bookings; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.channel_bookings (
    id bigint NOT NULL,
    channel character varying(191) DEFAULT 'other'::character varying NOT NULL,
    ota_booking_id character varying(191) NOT NULL,
    guest_name character varying(191) NOT NULL,
    guest_phone character varying(191),
    guest_email character varying(191),
    room_id bigint,
    check_in_date date NOT NULL,
    check_out_date date NOT NULL,
    nights integer DEFAULT 1 NOT NULL,
    rate_per_night numeric(8,2) DEFAULT '0'::numeric NOT NULL,
    total_amount numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    commission_pct numeric(5,2) DEFAULT '0'::numeric NOT NULL,
    net_amount numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    status character varying(191) DEFAULT 'pending'::character varying NOT NULL,
    converted_booking_id bigint,
    notes text,
    raw_data json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    hotel_id bigint
);


--
-- Name: channel_bookings_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.channel_bookings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: channel_bookings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.channel_bookings_id_seq OWNED BY public.channel_bookings.id;


--
-- Name: channel_manager_configs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.channel_manager_configs (
    id bigint NOT NULL,
    provider character varying(191) DEFAULT 'ezee'::character varying NOT NULL,
    api_key character varying(191),
    api_secret character varying(191),
    hotel_code character varying(191),
    property_id character varying(191),
    is_active boolean DEFAULT false NOT NULL,
    last_synced_at timestamp(0) without time zone,
    extra_config json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    hotel_id bigint
);


--
-- Name: channel_manager_configs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.channel_manager_configs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: channel_manager_configs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.channel_manager_configs_id_seq OWNED BY public.channel_manager_configs.id;


--
-- Name: channel_room_mappings; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.channel_room_mappings (
    id bigint NOT NULL,
    room_id bigint NOT NULL,
    channel_room_code character varying(191) NOT NULL,
    channel_rate_plan character varying(191),
    extra_bed_rate numeric(8,2) DEFAULT '0'::numeric NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    hotel_id bigint
);


--
-- Name: channel_room_mappings_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.channel_room_mappings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: channel_room_mappings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.channel_room_mappings_id_seq OWNED BY public.channel_room_mappings.id;


--
-- Name: customer_documents; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.customer_documents (
    id bigint NOT NULL,
    customer_id bigint NOT NULL,
    document_type character varying(191) NOT NULL,
    document_number character varying(191),
    file_name character varying(191) NOT NULL,
    file_path character varying(191) NOT NULL,
    file_type character varying(191),
    file_size integer,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: customer_documents_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.customer_documents_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: customer_documents_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.customer_documents_id_seq OWNED BY public.customer_documents.id;


--
-- Name: customers; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.customers (
    id bigint NOT NULL,
    name character varying(191) NOT NULL,
    email character varying(191),
    phone character varying(191) NOT NULL,
    address text,
    city character varying(191),
    state character varying(191),
    country character varying(191) DEFAULT 'India'::character varying NOT NULL,
    id_type character varying(191) DEFAULT 'aadhaar'::character varying NOT NULL,
    id_number character varying(191) NOT NULL,
    date_of_birth date,
    nationality character varying(191) DEFAULT 'Indian'::character varying NOT NULL,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    signature text,
    hotel_id bigint,
    deleted_at timestamp(0) without time zone,
    age smallint
);


--
-- Name: customers_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.customers_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: customers_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.customers_id_seq OWNED BY public.customers.id;


--
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(191) NOT NULL,
    connection text NOT NULL,
    queue text NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;


--
-- Name: fcm_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.fcm_tokens (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    hotel_id bigint,
    token text NOT NULL,
    platform character varying(255) DEFAULT 'web'::character varying NOT NULL,
    device_id character varying(191),
    last_seen_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT fcm_tokens_platform_check CHECK (((platform)::text = ANY ((ARRAY['web'::character varying, 'android'::character varying, 'ios'::character varying])::text[])))
);


--
-- Name: fcm_tokens_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.fcm_tokens_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: fcm_tokens_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.fcm_tokens_id_seq OWNED BY public.fcm_tokens.id;


--
-- Name: hotel_backup_settings; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.hotel_backup_settings (
    id bigint NOT NULL,
    hotel_id bigint NOT NULL,
    auto_backup_enabled boolean DEFAULT false NOT NULL,
    interval_hours integer DEFAULT 24 NOT NULL,
    retention_count integer DEFAULT 10 NOT NULL,
    last_backup_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: hotel_backup_settings_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.hotel_backup_settings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: hotel_backup_settings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.hotel_backup_settings_id_seq OWNED BY public.hotel_backup_settings.id;


--
-- Name: hotel_backups; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.hotel_backups (
    id bigint NOT NULL,
    hotel_id bigint NOT NULL,
    backup_data jsonb NOT NULL,
    type character varying(20) DEFAULT 'manual'::character varying NOT NULL,
    created_by bigint,
    size_kb integer DEFAULT 0 NOT NULL,
    label character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: hotel_backups_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.hotel_backups_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: hotel_backups_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.hotel_backups_id_seq OWNED BY public.hotel_backups.id;


--
-- Name: hotel_time_slots; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.hotel_time_slots (
    id bigint NOT NULL,
    hotel_id bigint NOT NULL,
    name character varying(191) NOT NULL,
    start_time character varying(5) NOT NULL,
    end_time character varying(5) NOT NULL,
    is_overnight boolean DEFAULT false NOT NULL,
    base_price numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    description text,
    sort_order integer DEFAULT 0 NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: hotel_time_slots_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.hotel_time_slots_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: hotel_time_slots_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.hotel_time_slots_id_seq OWNED BY public.hotel_time_slots.id;


--
-- Name: hotel_users; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.hotel_users (
    id bigint NOT NULL,
    hotel_id bigint NOT NULL,
    user_id bigint NOT NULL,
    role character varying(191) DEFAULT 'Admin'::character varying NOT NULL,
    is_hotel_admin boolean DEFAULT false NOT NULL,
    status character varying(191) DEFAULT 'active'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: hotel_users_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.hotel_users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: hotel_users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.hotel_users_id_seq OWNED BY public.hotel_users.id;


--
-- Name: hotels; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.hotels (
    id bigint NOT NULL,
    name character varying(191) NOT NULL,
    slug character varying(191) NOT NULL,
    address text,
    phone character varying(191),
    email character varying(191),
    status character varying(191) DEFAULT 'active'::character varying NOT NULL,
    plan character varying(191) DEFAULT 'basic'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    trial_ends_at timestamp(0) without time zone,
    plan_expires_at timestamp(0) without time zone,
    max_rooms integer DEFAULT 50 NOT NULL,
    max_users integer DEFAULT 10 NOT NULL,
    admin_notes text,
    billing_cycle character varying(191) DEFAULT 'monthly'::character varying NOT NULL,
    custom_monthly_price integer,
    custom_yearly_price integer,
    backup_enabled boolean DEFAULT false NOT NULL
);


--
-- Name: hotels_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.hotels_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: hotels_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.hotels_id_seq OWNED BY public.hotels.id;


--
-- Name: invoices; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.invoices (
    id bigint NOT NULL,
    invoice_number character varying(191) NOT NULL,
    booking_id bigint NOT NULL,
    customer_id bigint NOT NULL,
    total_amount numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    paid_amount numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    balance numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    status character varying(191) DEFAULT 'unpaid'::character varying NOT NULL,
    issued_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    razorpay_payment_link_id character varying(191),
    razorpay_payment_link_url character varying(191),
    razorpay_payment_link_status character varying(191),
    hotel_id bigint
);


--
-- Name: invoices_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.invoices_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: invoices_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.invoices_id_seq OWNED BY public.invoices.id;


--
-- Name: job_batches; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.job_batches (
    id character varying(191) NOT NULL,
    name character varying(191) NOT NULL,
    total_jobs integer NOT NULL,
    pending_jobs integer NOT NULL,
    failed_jobs integer NOT NULL,
    failed_job_ids text NOT NULL,
    options text,
    cancelled_at integer,
    created_at integer NOT NULL,
    finished_at integer
);


--
-- Name: jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.jobs (
    id bigint NOT NULL,
    queue character varying(191) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);


--
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;


--
-- Name: migrations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(191) NOT NULL,
    batch integer NOT NULL
);


--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: modules; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.modules (
    id bigint NOT NULL,
    slug character varying(191) NOT NULL,
    name character varying(191) NOT NULL,
    description text,
    is_enabled boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    hotel_id bigint
);


--
-- Name: modules_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.modules_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: modules_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.modules_id_seq OWNED BY public.modules.id;


--
-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.password_reset_tokens (
    email character varying(191) NOT NULL,
    token character varying(191) NOT NULL,
    created_at timestamp(0) without time zone
);


--
-- Name: pathik_configs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.pathik_configs (
    id bigint NOT NULL,
    api_token character varying(64),
    is_active boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    hotel_id bigint
);


--
-- Name: pathik_configs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.pathik_configs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: pathik_configs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.pathik_configs_id_seq OWNED BY public.pathik_configs.id;


--
-- Name: payment_link_configs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.payment_link_configs (
    id bigint NOT NULL,
    upi_id character varying(191),
    upi_name character varying(191),
    upi_enabled boolean DEFAULT false NOT NULL,
    razorpay_key_id character varying(191),
    razorpay_key_secret character varying(191),
    razorpay_enabled boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    hotel_id bigint
);


--
-- Name: payment_link_configs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.payment_link_configs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: payment_link_configs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.payment_link_configs_id_seq OWNED BY public.payment_link_configs.id;


--
-- Name: payments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.payments (
    id bigint NOT NULL,
    booking_id bigint NOT NULL,
    customer_id bigint NOT NULL,
    amount numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    payment_method character varying(191) DEFAULT 'cash'::character varying NOT NULL,
    payment_type character varying(191) DEFAULT 'advance'::character varying NOT NULL,
    status character varying(191) DEFAULT 'completed'::character varying NOT NULL,
    transaction_id character varying(191),
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    hotel_id bigint
);


--
-- Name: payments_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.payments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: payments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.payments_id_seq OWNED BY public.payments.id;


--
-- Name: permissions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.permissions (
    id bigint NOT NULL,
    slug character varying(191) NOT NULL,
    label character varying(191) NOT NULL,
    module character varying(191) NOT NULL,
    sort_order integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: permissions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.permissions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: permissions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.permissions_id_seq OWNED BY public.permissions.id;


--
-- Name: platform_campaigns; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.platform_campaigns (
    id bigint NOT NULL,
    hotel_ids json,
    channel character varying(255) DEFAULT 'email'::character varying NOT NULL,
    template_key character varying(191),
    subject character varying(191),
    body text NOT NULL,
    sent_count integer DEFAULT 0 NOT NULL,
    delivered_count integer DEFAULT 0 NOT NULL,
    sent_by character varying(191),
    sent_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT platform_campaigns_channel_check CHECK (((channel)::text = ANY ((ARRAY['email'::character varying, 'whatsapp'::character varying, 'both'::character varying])::text[])))
);


--
-- Name: platform_campaigns_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.platform_campaigns_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: platform_campaigns_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.platform_campaigns_id_seq OWNED BY public.platform_campaigns.id;


--
-- Name: platform_firebase_settings; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.platform_firebase_settings (
    id bigint NOT NULL,
    firebase_project_id text,
    firebase_api_key text,
    firebase_messaging_sender_id text,
    firebase_app_id text,
    firebase_vapid_key text,
    fcm_server_key text,
    push_enabled boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: platform_firebase_settings_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.platform_firebase_settings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: platform_firebase_settings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.platform_firebase_settings_id_seq OWNED BY public.platform_firebase_settings.id;


--
-- Name: platform_notification_deliveries; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.platform_notification_deliveries (
    id bigint NOT NULL,
    notification_id bigint NOT NULL,
    user_id bigint NOT NULL,
    hotel_id bigint,
    is_read boolean DEFAULT false NOT NULL,
    read_at timestamp(0) without time zone,
    delivered_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: platform_notification_deliveries_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.platform_notification_deliveries_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: platform_notification_deliveries_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.platform_notification_deliveries_id_seq OWNED BY public.platform_notification_deliveries.id;


--
-- Name: platform_notifications; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.platform_notifications (
    id bigint NOT NULL,
    title character varying(191) NOT NULL,
    body text NOT NULL,
    icon_url character varying(191),
    action_url character varying(191),
    target character varying(255) DEFAULT 'all'::character varying NOT NULL,
    target_ids json,
    sent_count integer DEFAULT 0 NOT NULL,
    delivered_count integer DEFAULT 0 NOT NULL,
    sent_by character varying(191),
    sent_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT platform_notifications_target_check CHECK (((target)::text = ANY ((ARRAY['all'::character varying, 'hotel'::character varying, 'plan'::character varying, 'user'::character varying])::text[])))
);


--
-- Name: platform_notifications_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.platform_notifications_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: platform_notifications_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.platform_notifications_id_seq OWNED BY public.platform_notifications.id;


--
-- Name: platform_plans; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.platform_plans (
    id bigint NOT NULL,
    slug character varying(64) NOT NULL,
    label character varying(100) NOT NULL,
    color character varying(20) DEFAULT '#64748b'::character varying NOT NULL,
    monthly_price integer DEFAULT 0 NOT NULL,
    yearly_price integer DEFAULT 0 NOT NULL,
    max_rooms integer DEFAULT 50 NOT NULL,
    max_users integer DEFAULT 10 NOT NULL,
    features json,
    is_active boolean DEFAULT true NOT NULL,
    sort_order smallint DEFAULT '0'::smallint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: platform_plans_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.platform_plans_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: platform_plans_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.platform_plans_id_seq OWNED BY public.platform_plans.id;


--
-- Name: platform_recovery_codes; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.platform_recovery_codes (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    code_hash character varying(64) NOT NULL,
    used boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: platform_recovery_codes_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.platform_recovery_codes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: platform_recovery_codes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.platform_recovery_codes_id_seq OWNED BY public.platform_recovery_codes.id;


--
-- Name: platform_whatsapp_settings; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.platform_whatsapp_settings (
    id bigint NOT NULL,
    meta_app_id character varying(191),
    meta_app_secret character varying(191),
    meta_config_id character varying(191),
    saas_token text,
    saas_phone_number_id character varying(191),
    saas_waba_id character varying(191),
    webhook_verify_token character varying(191),
    is_saas_active boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: COLUMN platform_whatsapp_settings.meta_config_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.platform_whatsapp_settings.meta_config_id IS 'Business Login Configuration ID';


--
-- Name: COLUMN platform_whatsapp_settings.saas_token; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.platform_whatsapp_settings.saas_token IS 'System User Access Token for shared CRM number';


--
-- Name: COLUMN platform_whatsapp_settings.saas_waba_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.platform_whatsapp_settings.saas_waba_id IS 'WhatsApp Business Account ID for shared number';


--
-- Name: platform_whatsapp_settings_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.platform_whatsapp_settings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: platform_whatsapp_settings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.platform_whatsapp_settings_id_seq OWNED BY public.platform_whatsapp_settings.id;


--
-- Name: role_permissions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.role_permissions (
    role_id bigint NOT NULL,
    permission_id bigint NOT NULL
);


--
-- Name: roles; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.roles (
    id bigint NOT NULL,
    name character varying(191) NOT NULL,
    description character varying(191),
    is_system boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    hotel_id bigint
);


--
-- Name: roles_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.roles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: roles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.roles_id_seq OWNED BY public.roles.id;


--
-- Name: room_add_ons; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.room_add_ons (
    id bigint NOT NULL,
    hotel_id bigint NOT NULL,
    room_id bigint,
    name character varying(191) NOT NULL,
    price numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: room_add_ons_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.room_add_ons_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: room_add_ons_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.room_add_ons_id_seq OWNED BY public.room_add_ons.id;


--
-- Name: rooms; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.rooms (
    id bigint NOT NULL,
    room_number character varying(191) NOT NULL,
    type character varying(191) DEFAULT 'standard'::character varying NOT NULL,
    capacity integer DEFAULT 2 NOT NULL,
    price_per_night numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    floor integer,
    view character varying(191),
    amenities text,
    description text,
    status character varying(191) DEFAULT 'available'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    has_breakfast boolean DEFAULT false NOT NULL,
    breakfast_price numeric(10,2),
    has_lunch boolean DEFAULT false NOT NULL,
    lunch_price numeric(10,2),
    has_dinner boolean DEFAULT false NOT NULL,
    dinner_price numeric(10,2),
    has_extra_bed boolean DEFAULT false NOT NULL,
    extra_bed_price numeric(10,2),
    hotel_id bigint,
    pricing_type character varying(191) DEFAULT 'per_night'::character varying NOT NULL,
    hourly_rate numeric(10,2)
);


--
-- Name: rooms_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.rooms_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: rooms_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.rooms_id_seq OWNED BY public.rooms.id;


--
-- Name: sessions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sessions (
    id character varying(191) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


--
-- Name: settings; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.settings (
    id bigint NOT NULL,
    resort_name character varying(191) NOT NULL,
    address text NOT NULL,
    phone character varying(191) NOT NULL,
    email character varying(191) NOT NULL,
    website character varying(191),
    gst_number character varying(191),
    tax_rate character varying(191) DEFAULT '12'::character varying NOT NULL,
    currency character varying(191) DEFAULT 'INR'::character varying NOT NULL,
    currency_symbol character varying(191) DEFAULT 'Rs'::character varying NOT NULL,
    check_in_time character varying(191) DEFAULT '14:00'::character varying NOT NULL,
    check_out_time character varying(191) DEFAULT '11:00'::character varying NOT NULL,
    cancellation_policy text,
    logo character varying(191),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    tagline character varying(191),
    hotel_id bigint
);


--
-- Name: settings_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.settings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: settings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.settings_id_seq OWNED BY public.settings.id;


--
-- Name: users; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    name character varying(191) NOT NULL,
    email character varying(191) NOT NULL,
    email_verified_at timestamp(0) without time zone,
    password character varying(191) NOT NULL,
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    role character varying(191) DEFAULT 'Admin'::character varying NOT NULL,
    is_super_admin boolean DEFAULT false NOT NULL,
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    totp_secret text,
    totp_enabled boolean DEFAULT false NOT NULL,
    CONSTRAINT users_status_check CHECK (((status)::text = ANY ((ARRAY['active'::character varying, 'inactive'::character varying])::text[])))
);


--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: whatsapp_configs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.whatsapp_configs (
    id bigint NOT NULL,
    provider text DEFAULT 'meta'::character varying NOT NULL,
    api_key text,
    phone_number_id text,
    webhook_verify_token text,
    business_account_id text,
    test_phone text,
    is_active boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    hotel_id bigint,
    mode character varying(191) DEFAULT 'shared'::character varying NOT NULL,
    setup_step smallint DEFAULT '0'::smallint NOT NULL,
    setup_completed boolean DEFAULT false NOT NULL,
    waba_id character varying(191),
    access_token text
);


--
-- Name: COLUMN whatsapp_configs.mode; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.whatsapp_configs.mode IS 'shared = use CRM number, own = hotel own number';


--
-- Name: COLUMN whatsapp_configs.setup_step; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.whatsapp_configs.setup_step IS '0=not started,1=token obtained,2=webhook done,3=templates submitted,5=complete';


--
-- Name: COLUMN whatsapp_configs.waba_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.whatsapp_configs.waba_id IS 'WhatsApp Business Account ID from Embedded Signup';


--
-- Name: COLUMN whatsapp_configs.access_token; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.whatsapp_configs.access_token IS 'Long-lived access token from Meta Embedded Signup exchange';


--
-- Name: whatsapp_configs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.whatsapp_configs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: whatsapp_configs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.whatsapp_configs_id_seq OWNED BY public.whatsapp_configs.id;


--
-- Name: whatsapp_templates; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.whatsapp_templates (
    id bigint NOT NULL,
    trigger_event character varying(191) NOT NULL,
    template_name character varying(191) NOT NULL,
    message_body text NOT NULL,
    variables_hint text,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    hotel_id bigint,
    approval_status character varying(20) DEFAULT 'pending'::character varying NOT NULL,
    meta_template_id character varying(191),
    meta_status character varying(191) DEFAULT 'not_submitted'::character varying NOT NULL
);


--
-- Name: COLUMN whatsapp_templates.meta_template_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.whatsapp_templates.meta_template_id IS 'ID returned by Meta after template submission';


--
-- Name: COLUMN whatsapp_templates.meta_status; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.whatsapp_templates.meta_status IS 'not_submitted|submitted|approved|rejected';


--
-- Name: whatsapp_templates_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.whatsapp_templates_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: whatsapp_templates_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.whatsapp_templates_id_seq OWNED BY public.whatsapp_templates.id;


--
-- Name: activity_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.activity_logs ALTER COLUMN id SET DEFAULT nextval('public.activity_logs_id_seq'::regclass);


--
-- Name: booking_add_ons id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.booking_add_ons ALTER COLUMN id SET DEFAULT nextval('public.booking_add_ons_id_seq'::regclass);


--
-- Name: booking_extra_charges id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.booking_extra_charges ALTER COLUMN id SET DEFAULT nextval('public.booking_extra_charges_id_seq'::regclass);


--
-- Name: booking_guests id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.booking_guests ALTER COLUMN id SET DEFAULT nextval('public.booking_guests_id_seq'::regclass);


--
-- Name: bookings id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bookings ALTER COLUMN id SET DEFAULT nextval('public.bookings_id_seq'::regclass);


--
-- Name: channel_bookings id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.channel_bookings ALTER COLUMN id SET DEFAULT nextval('public.channel_bookings_id_seq'::regclass);


--
-- Name: channel_manager_configs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.channel_manager_configs ALTER COLUMN id SET DEFAULT nextval('public.channel_manager_configs_id_seq'::regclass);


--
-- Name: channel_room_mappings id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.channel_room_mappings ALTER COLUMN id SET DEFAULT nextval('public.channel_room_mappings_id_seq'::regclass);


--
-- Name: customer_documents id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.customer_documents ALTER COLUMN id SET DEFAULT nextval('public.customer_documents_id_seq'::regclass);


--
-- Name: customers id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.customers ALTER COLUMN id SET DEFAULT nextval('public.customers_id_seq'::regclass);


--
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- Name: fcm_tokens id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.fcm_tokens ALTER COLUMN id SET DEFAULT nextval('public.fcm_tokens_id_seq'::regclass);


--
-- Name: hotel_backup_settings id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hotel_backup_settings ALTER COLUMN id SET DEFAULT nextval('public.hotel_backup_settings_id_seq'::regclass);


--
-- Name: hotel_backups id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hotel_backups ALTER COLUMN id SET DEFAULT nextval('public.hotel_backups_id_seq'::regclass);


--
-- Name: hotel_time_slots id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hotel_time_slots ALTER COLUMN id SET DEFAULT nextval('public.hotel_time_slots_id_seq'::regclass);


--
-- Name: hotel_users id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hotel_users ALTER COLUMN id SET DEFAULT nextval('public.hotel_users_id_seq'::regclass);


--
-- Name: hotels id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hotels ALTER COLUMN id SET DEFAULT nextval('public.hotels_id_seq'::regclass);


--
-- Name: invoices id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invoices ALTER COLUMN id SET DEFAULT nextval('public.invoices_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: modules id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.modules ALTER COLUMN id SET DEFAULT nextval('public.modules_id_seq'::regclass);


--
-- Name: pathik_configs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pathik_configs ALTER COLUMN id SET DEFAULT nextval('public.pathik_configs_id_seq'::regclass);


--
-- Name: payment_link_configs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment_link_configs ALTER COLUMN id SET DEFAULT nextval('public.payment_link_configs_id_seq'::regclass);


--
-- Name: payments id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payments ALTER COLUMN id SET DEFAULT nextval('public.payments_id_seq'::regclass);


--
-- Name: permissions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permissions ALTER COLUMN id SET DEFAULT nextval('public.permissions_id_seq'::regclass);


--
-- Name: platform_campaigns id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.platform_campaigns ALTER COLUMN id SET DEFAULT nextval('public.platform_campaigns_id_seq'::regclass);


--
-- Name: platform_firebase_settings id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.platform_firebase_settings ALTER COLUMN id SET DEFAULT nextval('public.platform_firebase_settings_id_seq'::regclass);


--
-- Name: platform_notification_deliveries id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.platform_notification_deliveries ALTER COLUMN id SET DEFAULT nextval('public.platform_notification_deliveries_id_seq'::regclass);


--
-- Name: platform_notifications id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.platform_notifications ALTER COLUMN id SET DEFAULT nextval('public.platform_notifications_id_seq'::regclass);


--
-- Name: platform_plans id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.platform_plans ALTER COLUMN id SET DEFAULT nextval('public.platform_plans_id_seq'::regclass);


--
-- Name: platform_recovery_codes id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.platform_recovery_codes ALTER COLUMN id SET DEFAULT nextval('public.platform_recovery_codes_id_seq'::regclass);


--
-- Name: platform_whatsapp_settings id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.platform_whatsapp_settings ALTER COLUMN id SET DEFAULT nextval('public.platform_whatsapp_settings_id_seq'::regclass);


--
-- Name: roles id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles ALTER COLUMN id SET DEFAULT nextval('public.roles_id_seq'::regclass);


--
-- Name: room_add_ons id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.room_add_ons ALTER COLUMN id SET DEFAULT nextval('public.room_add_ons_id_seq'::regclass);


--
-- Name: rooms id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rooms ALTER COLUMN id SET DEFAULT nextval('public.rooms_id_seq'::regclass);


--
-- Name: settings id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.settings ALTER COLUMN id SET DEFAULT nextval('public.settings_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Name: whatsapp_configs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.whatsapp_configs ALTER COLUMN id SET DEFAULT nextval('public.whatsapp_configs_id_seq'::regclass);


--
-- Name: whatsapp_templates id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.whatsapp_templates ALTER COLUMN id SET DEFAULT nextval('public.whatsapp_templates_id_seq'::regclass);


--
-- Name: activity_logs activity_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.activity_logs
    ADD CONSTRAINT activity_logs_pkey PRIMARY KEY (id);


--
-- Name: booking_add_ons booking_add_ons_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.booking_add_ons
    ADD CONSTRAINT booking_add_ons_pkey PRIMARY KEY (id);


--
-- Name: booking_extra_charges booking_extra_charges_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.booking_extra_charges
    ADD CONSTRAINT booking_extra_charges_pkey PRIMARY KEY (id);


--
-- Name: booking_guests booking_guests_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.booking_guests
    ADD CONSTRAINT booking_guests_pkey PRIMARY KEY (id);


--
-- Name: bookings bookings_booking_number_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bookings
    ADD CONSTRAINT bookings_booking_number_unique UNIQUE (booking_number);


--
-- Name: bookings bookings_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bookings
    ADD CONSTRAINT bookings_pkey PRIMARY KEY (id);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: channel_bookings channel_bookings_ota_booking_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.channel_bookings
    ADD CONSTRAINT channel_bookings_ota_booking_id_unique UNIQUE (ota_booking_id);


--
-- Name: channel_bookings channel_bookings_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.channel_bookings
    ADD CONSTRAINT channel_bookings_pkey PRIMARY KEY (id);


--
-- Name: channel_manager_configs channel_manager_configs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.channel_manager_configs
    ADD CONSTRAINT channel_manager_configs_pkey PRIMARY KEY (id);


--
-- Name: channel_room_mappings channel_room_mappings_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.channel_room_mappings
    ADD CONSTRAINT channel_room_mappings_pkey PRIMARY KEY (id);


--
-- Name: channel_room_mappings channel_room_mappings_room_id_channel_room_code_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.channel_room_mappings
    ADD CONSTRAINT channel_room_mappings_room_id_channel_room_code_unique UNIQUE (room_id, channel_room_code);


--
-- Name: customer_documents customer_documents_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.customer_documents
    ADD CONSTRAINT customer_documents_pkey PRIMARY KEY (id);


--
-- Name: customers customers_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.customers
    ADD CONSTRAINT customers_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- Name: fcm_tokens fcm_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.fcm_tokens
    ADD CONSTRAINT fcm_tokens_pkey PRIMARY KEY (id);


--
-- Name: hotel_backup_settings hotel_backup_settings_hotel_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hotel_backup_settings
    ADD CONSTRAINT hotel_backup_settings_hotel_id_unique UNIQUE (hotel_id);


--
-- Name: hotel_backup_settings hotel_backup_settings_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hotel_backup_settings
    ADD CONSTRAINT hotel_backup_settings_pkey PRIMARY KEY (id);


--
-- Name: hotel_backups hotel_backups_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hotel_backups
    ADD CONSTRAINT hotel_backups_pkey PRIMARY KEY (id);


--
-- Name: hotel_time_slots hotel_time_slots_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hotel_time_slots
    ADD CONSTRAINT hotel_time_slots_pkey PRIMARY KEY (id);


--
-- Name: hotel_users hotel_users_hotel_id_user_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hotel_users
    ADD CONSTRAINT hotel_users_hotel_id_user_id_unique UNIQUE (hotel_id, user_id);


--
-- Name: hotel_users hotel_users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hotel_users
    ADD CONSTRAINT hotel_users_pkey PRIMARY KEY (id);


--
-- Name: hotels hotels_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hotels
    ADD CONSTRAINT hotels_pkey PRIMARY KEY (id);


--
-- Name: hotels hotels_slug_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hotels
    ADD CONSTRAINT hotels_slug_unique UNIQUE (slug);


--
-- Name: invoices invoices_invoice_number_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invoices
    ADD CONSTRAINT invoices_invoice_number_unique UNIQUE (invoice_number);


--
-- Name: invoices invoices_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invoices
    ADD CONSTRAINT invoices_pkey PRIMARY KEY (id);


--
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: modules modules_hotel_id_slug_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.modules
    ADD CONSTRAINT modules_hotel_id_slug_unique UNIQUE (hotel_id, slug);


--
-- Name: modules modules_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.modules
    ADD CONSTRAINT modules_pkey PRIMARY KEY (id);


--
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);


--
-- Name: pathik_configs pathik_configs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pathik_configs
    ADD CONSTRAINT pathik_configs_pkey PRIMARY KEY (id);


--
-- Name: payment_link_configs payment_link_configs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment_link_configs
    ADD CONSTRAINT payment_link_configs_pkey PRIMARY KEY (id);


--
-- Name: payments payments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payments
    ADD CONSTRAINT payments_pkey PRIMARY KEY (id);


--
-- Name: permissions permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permissions
    ADD CONSTRAINT permissions_pkey PRIMARY KEY (id);


--
-- Name: permissions permissions_slug_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permissions
    ADD CONSTRAINT permissions_slug_unique UNIQUE (slug);


--
-- Name: platform_campaigns platform_campaigns_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.platform_campaigns
    ADD CONSTRAINT platform_campaigns_pkey PRIMARY KEY (id);


--
-- Name: platform_firebase_settings platform_firebase_settings_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.platform_firebase_settings
    ADD CONSTRAINT platform_firebase_settings_pkey PRIMARY KEY (id);


--
-- Name: platform_notification_deliveries platform_notification_deliveries_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.platform_notification_deliveries
    ADD CONSTRAINT platform_notification_deliveries_pkey PRIMARY KEY (id);


--
-- Name: platform_notifications platform_notifications_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.platform_notifications
    ADD CONSTRAINT platform_notifications_pkey PRIMARY KEY (id);


--
-- Name: platform_plans platform_plans_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.platform_plans
    ADD CONSTRAINT platform_plans_pkey PRIMARY KEY (id);


--
-- Name: platform_plans platform_plans_slug_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.platform_plans
    ADD CONSTRAINT platform_plans_slug_unique UNIQUE (slug);


--
-- Name: platform_recovery_codes platform_recovery_codes_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.platform_recovery_codes
    ADD CONSTRAINT platform_recovery_codes_pkey PRIMARY KEY (id);


--
-- Name: platform_whatsapp_settings platform_whatsapp_settings_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.platform_whatsapp_settings
    ADD CONSTRAINT platform_whatsapp_settings_pkey PRIMARY KEY (id);


--
-- Name: role_permissions role_permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role_permissions
    ADD CONSTRAINT role_permissions_pkey PRIMARY KEY (role_id, permission_id);


--
-- Name: roles roles_hotel_id_name_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_hotel_id_name_unique UNIQUE (hotel_id, name);


--
-- Name: roles roles_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_pkey PRIMARY KEY (id);


--
-- Name: room_add_ons room_add_ons_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.room_add_ons
    ADD CONSTRAINT room_add_ons_pkey PRIMARY KEY (id);


--
-- Name: rooms rooms_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rooms
    ADD CONSTRAINT rooms_pkey PRIMARY KEY (id);


--
-- Name: rooms rooms_room_number_hotel_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rooms
    ADD CONSTRAINT rooms_room_number_hotel_unique UNIQUE (room_number, hotel_id);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: settings settings_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.settings
    ADD CONSTRAINT settings_pkey PRIMARY KEY (id);


--
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: whatsapp_configs whatsapp_configs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.whatsapp_configs
    ADD CONSTRAINT whatsapp_configs_pkey PRIMARY KEY (id);


--
-- Name: whatsapp_templates whatsapp_templates_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.whatsapp_templates
    ADD CONSTRAINT whatsapp_templates_pkey PRIMARY KEY (id);


--
-- Name: activity_logs_hotel_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX activity_logs_hotel_id_index ON public.activity_logs USING btree (hotel_id);


--
-- Name: booking_add_ons_booking_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX booking_add_ons_booking_id_index ON public.booking_add_ons USING btree (booking_id);


--
-- Name: booking_extra_charges_booking_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX booking_extra_charges_booking_id_index ON public.booking_extra_charges USING btree (booking_id);


--
-- Name: bookings_booking_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX bookings_booking_date_index ON public.bookings USING btree (booking_date);


--
-- Name: bookings_hotel_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX bookings_hotel_id_index ON public.bookings USING btree (hotel_id);


--
-- Name: bookings_time_slot_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX bookings_time_slot_id_index ON public.bookings USING btree (time_slot_id);


--
-- Name: cache_expiration_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cache_expiration_index ON public.cache USING btree (expiration);


--
-- Name: cache_locks_expiration_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cache_locks_expiration_index ON public.cache_locks USING btree (expiration);


--
-- Name: channel_bookings_hotel_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX channel_bookings_hotel_id_index ON public.channel_bookings USING btree (hotel_id);


--
-- Name: channel_manager_configs_hotel_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX channel_manager_configs_hotel_id_index ON public.channel_manager_configs USING btree (hotel_id);


--
-- Name: channel_room_mappings_hotel_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX channel_room_mappings_hotel_id_index ON public.channel_room_mappings USING btree (hotel_id);


--
-- Name: customers_hotel_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX customers_hotel_id_index ON public.customers USING btree (hotel_id);


--
-- Name: fcm_tokens_token_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX fcm_tokens_token_index ON public.fcm_tokens USING btree (token);


--
-- Name: fcm_tokens_user_id_hotel_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX fcm_tokens_user_id_hotel_id_index ON public.fcm_tokens USING btree (user_id, hotel_id);


--
-- Name: hotel_backups_hotel_id_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX hotel_backups_hotel_id_created_at_index ON public.hotel_backups USING btree (hotel_id, created_at);


--
-- Name: hotel_time_slots_hotel_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX hotel_time_slots_hotel_id_index ON public.hotel_time_slots USING btree (hotel_id);


--
-- Name: hotel_users_hotel_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX hotel_users_hotel_id_index ON public.hotel_users USING btree (hotel_id);


--
-- Name: hotel_users_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX hotel_users_user_id_index ON public.hotel_users USING btree (user_id);


--
-- Name: invoices_hotel_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX invoices_hotel_id_index ON public.invoices USING btree (hotel_id);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: modules_hotel_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX modules_hotel_id_index ON public.modules USING btree (hotel_id);


--
-- Name: pathik_configs_hotel_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX pathik_configs_hotel_id_index ON public.pathik_configs USING btree (hotel_id);


--
-- Name: payment_link_configs_hotel_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX payment_link_configs_hotel_id_index ON public.payment_link_configs USING btree (hotel_id);


--
-- Name: payments_hotel_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX payments_hotel_id_index ON public.payments USING btree (hotel_id);


--
-- Name: platform_notification_deliveries_hotel_id_is_read_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX platform_notification_deliveries_hotel_id_is_read_index ON public.platform_notification_deliveries USING btree (hotel_id, is_read);


--
-- Name: platform_notification_deliveries_notification_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX platform_notification_deliveries_notification_id_index ON public.platform_notification_deliveries USING btree (notification_id);


--
-- Name: platform_notification_deliveries_user_id_is_read_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX platform_notification_deliveries_user_id_is_read_index ON public.platform_notification_deliveries USING btree (user_id, is_read);


--
-- Name: platform_recovery_codes_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX platform_recovery_codes_user_id_index ON public.platform_recovery_codes USING btree (user_id);


--
-- Name: roles_hotel_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX roles_hotel_id_index ON public.roles USING btree (hotel_id);


--
-- Name: room_add_ons_hotel_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX room_add_ons_hotel_id_index ON public.room_add_ons USING btree (hotel_id);


--
-- Name: room_add_ons_room_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX room_add_ons_room_id_index ON public.room_add_ons USING btree (room_id);


--
-- Name: rooms_hotel_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX rooms_hotel_id_index ON public.rooms USING btree (hotel_id);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: settings_hotel_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX settings_hotel_id_index ON public.settings USING btree (hotel_id);


--
-- Name: whatsapp_configs_hotel_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX whatsapp_configs_hotel_id_index ON public.whatsapp_configs USING btree (hotel_id);


--
-- Name: whatsapp_templates_hotel_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX whatsapp_templates_hotel_id_index ON public.whatsapp_templates USING btree (hotel_id);


--
-- Name: booking_add_ons booking_add_ons_booking_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.booking_add_ons
    ADD CONSTRAINT booking_add_ons_booking_id_foreign FOREIGN KEY (booking_id) REFERENCES public.bookings(id) ON DELETE CASCADE;


--
-- Name: booking_extra_charges booking_extra_charges_booking_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.booking_extra_charges
    ADD CONSTRAINT booking_extra_charges_booking_id_foreign FOREIGN KEY (booking_id) REFERENCES public.bookings(id) ON DELETE CASCADE;


--
-- Name: booking_guests booking_guests_booking_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.booking_guests
    ADD CONSTRAINT booking_guests_booking_id_foreign FOREIGN KEY (booking_id) REFERENCES public.bookings(id) ON DELETE CASCADE;


--
-- Name: bookings bookings_customer_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bookings
    ADD CONSTRAINT bookings_customer_id_foreign FOREIGN KEY (customer_id) REFERENCES public.customers(id) ON DELETE CASCADE;


--
-- Name: bookings bookings_room_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bookings
    ADD CONSTRAINT bookings_room_id_foreign FOREIGN KEY (room_id) REFERENCES public.rooms(id) ON DELETE CASCADE;


--
-- Name: bookings bookings_time_slot_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bookings
    ADD CONSTRAINT bookings_time_slot_id_foreign FOREIGN KEY (time_slot_id) REFERENCES public.hotel_time_slots(id) ON DELETE SET NULL;


--
-- Name: channel_bookings channel_bookings_converted_booking_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.channel_bookings
    ADD CONSTRAINT channel_bookings_converted_booking_id_foreign FOREIGN KEY (converted_booking_id) REFERENCES public.bookings(id) ON DELETE SET NULL;


--
-- Name: channel_bookings channel_bookings_room_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.channel_bookings
    ADD CONSTRAINT channel_bookings_room_id_foreign FOREIGN KEY (room_id) REFERENCES public.rooms(id) ON DELETE SET NULL;


--
-- Name: channel_room_mappings channel_room_mappings_room_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.channel_room_mappings
    ADD CONSTRAINT channel_room_mappings_room_id_foreign FOREIGN KEY (room_id) REFERENCES public.rooms(id) ON DELETE CASCADE;


--
-- Name: customer_documents customer_documents_customer_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.customer_documents
    ADD CONSTRAINT customer_documents_customer_id_foreign FOREIGN KEY (customer_id) REFERENCES public.customers(id) ON DELETE CASCADE;


--
-- Name: hotel_backup_settings hotel_backup_settings_hotel_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hotel_backup_settings
    ADD CONSTRAINT hotel_backup_settings_hotel_id_foreign FOREIGN KEY (hotel_id) REFERENCES public.hotels(id) ON DELETE CASCADE;


--
-- Name: hotel_backups hotel_backups_hotel_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hotel_backups
    ADD CONSTRAINT hotel_backups_hotel_id_foreign FOREIGN KEY (hotel_id) REFERENCES public.hotels(id) ON DELETE CASCADE;


--
-- Name: hotel_time_slots hotel_time_slots_hotel_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hotel_time_slots
    ADD CONSTRAINT hotel_time_slots_hotel_id_foreign FOREIGN KEY (hotel_id) REFERENCES public.hotels(id) ON DELETE CASCADE;


--
-- Name: invoices invoices_booking_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invoices
    ADD CONSTRAINT invoices_booking_id_foreign FOREIGN KEY (booking_id) REFERENCES public.bookings(id) ON DELETE CASCADE;


--
-- Name: invoices invoices_customer_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invoices
    ADD CONSTRAINT invoices_customer_id_foreign FOREIGN KEY (customer_id) REFERENCES public.customers(id) ON DELETE CASCADE;


--
-- Name: payments payments_booking_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payments
    ADD CONSTRAINT payments_booking_id_foreign FOREIGN KEY (booking_id) REFERENCES public.bookings(id) ON DELETE CASCADE;


--
-- Name: payments payments_customer_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payments
    ADD CONSTRAINT payments_customer_id_foreign FOREIGN KEY (customer_id) REFERENCES public.customers(id) ON DELETE CASCADE;


--
-- Name: role_permissions role_permissions_permission_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role_permissions
    ADD CONSTRAINT role_permissions_permission_id_foreign FOREIGN KEY (permission_id) REFERENCES public.permissions(id) ON DELETE CASCADE;


--
-- Name: role_permissions role_permissions_role_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role_permissions
    ADD CONSTRAINT role_permissions_role_id_foreign FOREIGN KEY (role_id) REFERENCES public.roles(id) ON DELETE CASCADE;


--
-- Name: room_add_ons room_add_ons_hotel_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.room_add_ons
    ADD CONSTRAINT room_add_ons_hotel_id_foreign FOREIGN KEY (hotel_id) REFERENCES public.hotels(id) ON DELETE CASCADE;


--
-- Name: room_add_ons room_add_ons_room_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.room_add_ons
    ADD CONSTRAINT room_add_ons_room_id_foreign FOREIGN KEY (room_id) REFERENCES public.rooms(id) ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

\unrestrict Ums4SncGNqaFhiFZQDCwY2eXRRWRfp6FAQlWuueJZAs1JGhU1YcRGo00vdFwBpv

--
-- PostgreSQL database dump
--

\restrict 8pkX21ebXnWAaHIqhXMv11FYPULIbESg5kQx0ePTjqGZTeKI5xRkRorgA06WwRF

-- Dumped from database version 16.10
-- Dumped by pg_dump version 16.10

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.migrations (id, migration, batch) FROM stdin;
49	0001_01_01_000000_create_users_table	1
50	0001_01_01_000001_create_cache_table	1
51	0001_01_01_000002_create_jobs_table	1
52	2024_01_01_000010_create_customers_table	1
53	2024_01_01_000011_create_customer_documents_table	1
54	2024_01_01_000012_create_rooms_table	1
55	2024_01_01_000013_create_bookings_table	1
56	2024_01_01_000014_create_payments_table	1
57	2024_01_01_000015_create_settings_table	1
58	2024_01_01_100000_create_customers_table	1
59	2024_01_01_100001_create_customer_documents_table	1
60	2024_01_01_100002_create_rooms_table	1
61	2024_01_01_100003_create_bookings_table	1
62	2024_01_01_100004_create_payments_table	1
63	2024_01_01_100005_create_invoices_table	1
64	2024_01_01_100006_create_settings_table	1
65	2026_03_06_110757_make_id_number_nullable_on_customers	1
66	2026_03_06_111328_add_tagline_to_settings_table	1
67	2026_03_06_112752_create_permissions_table	1
68	2026_03_06_112752_create_roles_table	1
69	2026_03_06_112753_create_activity_logs_table	1
70	2026_03_06_112753_create_role_permissions_table	1
71	2026_03_06_122537_add_role_to_users_table	1
72	2026_03_09_120343_add_meal_options_to_rooms_table	1
73	2026_03_09_120343_add_meal_plan_to_bookings_table	1
74	2026_03_09_121807_add_extra_bed_to_bookings_table	1
75	2026_03_09_121807_add_extra_bed_to_rooms_table	1
76	2026_03_10_000001_create_pathik_configs_table	1
77	2026_03_10_091102_create_modules_table	1
78	2026_03_10_091102_create_whatsapp_configs_table	1
79	2026_03_10_091103_create_whatsapp_templates_table	1
80	2026_03_10_113246_create_channel_manager_configs_table	1
81	2026_03_10_113246_create_channel_room_mappings_table	1
82	2026_03_10_113247_create_channel_bookings_table	1
83	2026_03_10_200000_create_payment_link_configs_table	1
84	2026_03_10_210001_add_new_whatsapp_templates	1
85	2026_03_10_210002_create_booking_guests_table	1
86	2026_03_28_175437_add_signature_to_customers_table	1
87	2026_03_31_000001_create_hotels_table	1
88	2026_03_31_000002_create_hotel_users_table	1
89	2026_03_31_000003_add_hotel_id_to_all_tables	1
90	2026_03_31_095532_add_saas_fields_to_hotels_table	1
91	2026_03_31_115840_add_totp_to_users_table	1
92	2026_03_31_120441_create_platform_recovery_codes_table	1
93	2026_03_31_121504_fix_room_number_unique_per_hotel	1
94	2026_03_31_122824_add_billing_fields_to_hotels_table	1
95	2026_03_31_200000_create_platform_plans_table	1
96	2026_04_01_100523_add_deleted_at_to_customers_table	1
97	2026_04_01_131733_add_age_to_customers_table	2
98	2026_04_02_084748_seed_time_slot_pricing_module_for_existing_hotels	3
99	2026_04_02_100000_create_hotel_time_slots_table	4
100	2026_04_02_100001_create_room_add_ons_table	4
101	2026_04_02_100002_create_booking_add_ons_table	4
102	2026_04_02_100003_add_pricing_type_to_rooms_table	4
103	2026_04_02_100004_add_slot_fields_to_bookings_table	4
104	2026_04_02_100005_seed_hourly_pricing_module_for_existing_hotels	5
105	2026_04_03_055650_create_hotel_backup_settings_table	6
106	2026_04_03_055651_create_hotel_backups_table	6
107	2026_04_03_060346_add_backup_enabled_to_hotels_table	6
108	2026_04_03_060347_change_backup_data_to_json_in_hotel_backups	6
109	2026_04_03_063840_seed_default_backup_settings_for_existing_hotels	6
110	2026_04_03_100629_expand_whatsapp_config_columns_to_text	7
111	2026_04_03_094540_backfill_missing_modules_for_all_hotels	8
112	2026_04_06_100001_create_booking_extra_charges_table	8
113	2026_04_06_100002_seed_extra_billing_module	8
114	2026_04_06_125017_add_approval_status_to_whatsapp_templates	9
115	2026_04_07_054359_create_platform_whatsapp_settings_table	10
116	2026_04_07_054400_add_mode_setup_to_whatsapp_configs	10
117	2026_04_07_054401_add_meta_fields_to_whatsapp_templates	10
118	2026_04_09_100001_create_platform_campaigns_table	11
119	2026_04_09_100002_create_platform_firebase_settings_table	11
120	2026_04_09_100003_create_fcm_tokens_table	11
121	2026_04_09_100004_create_platform_notifications_table	11
122	2026_04_09_100005_create_platform_notification_deliveries_table	11
\.


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.migrations_id_seq', 122, true);


--
-- PostgreSQL database dump complete
--

\unrestrict 8pkX21ebXnWAaHIqhXMv11FYPULIbESg5kQx0ePTjqGZTeKI5xRkRorgA06WwRF

