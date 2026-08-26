

CREATE DATABASE IF NOT EXISTS crypt_of_secrets
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE crypt_of_secrets;
-- use database


-- animal avatars for profiles after getting enoguh trust --
CREATE TABLE animal_avatars (
    avatar_id   INT AUTO_INCREMENT PRIMARY KEY,
    animal_name VARCHAR(50)  NOT NULL UNIQUE,
    filename    VARCHAR(255) NOT NULL,
    min_trust   INT          NOT NULL DEFAULT 50
) ENGINE=InnoDB;


-- the buffs that can be applied to posts --
--  id of tarot, name, the name of the file, the effects that are predetermined, the value, duration and rarity --
CREATE TABLE tarot_card_buffs (
    tarot_id      INT AUTO_INCREMENT PRIMARY KEY,
    tarot_name    VARCHAR(100) NOT NULL,
    icon_filename VARCHAR(100) NOT NULL,
    back_filename VARCHAR(100) NULL,
    effect_type   ENUM(
        'score_multiplier','vote_weight','hide_false_votes','pin_position',
        'reset_false_votes','piece_drop_rate','voter_reward','reset_ratio',
        'vote_trickle','feed_priority'
    ) NOT NULL,
    effect_value  DECIMAL(5,2) NULL,
    effect_text   VARCHAR(255) NOT NULL,
    buff_duration INT          NULL COMMENT 'minutes; NULL = instant effect',
    rarity        TINYINT      NOT NULL DEFAULT 1
) ENGINE=InnoDB;


--  user tables --
-- everything linked to users --
-- potential 2fa ?? --
CREATE TABLE users (
    user_id       INT AUTO_INCREMENT PRIMARY KEY,

    email         VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,

    totp_secret   VARCHAR(64)  NULL,
    totp_enabled  BOOLEAN      NOT NULL DEFAULT FALSE,

    anon_handle     VARCHAR(30) NOT NULL UNIQUE,
    is_anonymous    BOOLEAN     NOT NULL DEFAULT TRUE,
    animal_username VARCHAR(50) NULL UNIQUE,
    avatar_id       INT         NULL,

    custom_avatar        VARCHAR(255) NULL,
    custom_avatar_status ENUM('none','pending','approved','rejected')
                         NOT NULL DEFAULT 'none',

    trust_index INT  NOT NULL DEFAULT 0,
    role        ENUM('user','leader') NOT NULL DEFAULT 'user',

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME NULL,

    FOREIGN KEY (avatar_id) REFERENCES animal_avatars(avatar_id)
        ON DELETE SET NULL,
    INDEX idx_trust (trust_index)
) ENGINE=InnoDB;


-- post table --
-- everything linked to posts --
CREATE TABLE posts (
    post_id   INT AUTO_INCREMENT PRIMARY KEY,
    author_id INT          NOT NULL,
    title     VARCHAR(200) NOT NULL,
    content   TEXT         NOT NULL,

    image_filename VARCHAR(255) NULL,

    status ENUM('draft','pending','approved','rejected')
           NOT NULL DEFAULT 'pending',


    posted_anonymously BOOLEAN NOT NULL DEFAULT TRUE,

    active_buff_id  INT      NULL,
    buff_expires_at DATETIME NULL,
    manual_boost    INT      NOT NULL DEFAULT 0,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (author_id)      REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (active_buff_id) REFERENCES tarot_card_buffs(tarot_id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;


-- the true vtes onto posts --
CREATE TABLE truth_voting (
    vote_id    INT AUTO_INCREMENT PRIMARY KEY,
    post_id    INT      NOT NULL,
    voter_id   INT      NOT NULL,
    is_true    BOOLEAN  NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (post_id)  REFERENCES posts(post_id) ON DELETE CASCADE,
    FOREIGN KEY (voter_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY uniq_vote (post_id, voter_id)
) ENGINE=InnoDB;


-- you collect 4 peices --
CREATE TABLE user_tarot_pieces (
    piece_id     INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT      NOT NULL,
    tarot_id     INT      NOT NULL,
    piece_number TINYINT  NOT NULL COMMENT '1-4',
    earned_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)  REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (tarot_id) REFERENCES tarot_card_buffs(tarot_id) ON DELETE CASCADE,
    INDEX idx_user_card (user_id, tarot_id)
) ENGINE=InnoDB;


-- tarot cards owned / collecting --
CREATE TABLE award_collection (
    collection_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id       INT NOT NULL,
    tarot_id      INT NOT NULL,
    quantity      INT NOT NULL DEFAULT 0,

    FOREIGN KEY (user_id)  REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (tarot_id) REFERENCES tarot_card_buffs(tarot_id) ON DELETE CASCADE,
    UNIQUE KEY uniq_holding (user_id, tarot_id)
) ENGINE=InnoDB;


-- awards given on a specifc post --
CREATE TABLE post_awards (
    award_instance_id INT AUTO_INCREMENT PRIMARY KEY,
    post_id  INT      NOT NULL,
    giver_id INT      NOT NULL,
    tarot_id INT      NOT NULL,
    given_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (post_id)  REFERENCES posts(post_id) ON DELETE CASCADE,
    FOREIGN KEY (giver_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (tarot_id) REFERENCES tarot_card_buffs(tarot_id) ON DELETE CASCADE
) ENGINE=InnoDB;


-- if you get enough trust index you can submit your own animal pfp to be reviewed --
CREATE TABLE avatar_submissions (
    submission_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id       INT          NOT NULL,
    filename      VARCHAR(255) NOT NULL,
    status        ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    reviewed_by   INT          NULL,
    review_note   VARCHAR(255) NULL,
    submitted_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_at   DATETIME     NULL,

    FOREIGN KEY (user_id)     REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- notifications potentially for when posts are approced --
CREATE TABLE notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    type       ENUM('post_approved','post_rejected','award_received') NOT NULL,
    post_id    INT NULL,
    is_read    BOOLEAN NOT NULL DEFAULT FALSE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (post_id) REFERENCES posts(post_id) ON DELETE CASCADE
) ENGINE=InnoDB;


-- files for the avatars and the trust needed to use them --
INSERT INTO animal_avatars (animal_name, filename, min_trust) VALUES
    ('cow',       'Temp_CowIcon.webp',        50),
    ('frog',      'Temp_FrogIcon.webp',       50),
    ('bee',       'Temp_BeeIcon.webp',        50),
    ('ladybug',   'Temp_LadybugIcon.webp',    50),
    ('turtle',    'Temp_TurtleIcon.webp',     50),
    ('bat',       'Temp_BatIcon.webp',        50),
    ('bear',      'Temp_BearIcon.webp',      120),
    ('panda',     'Temp_PandaIcon.webp',     120),
    ('capybara',  'Temp_CapybaraIcon.webp',  200),
    ('crocodile', 'Temp_CrocodileIcon.webp', 200);

-- the values of the buffs, names of the files, the value, duration etc --
INSERT INTO tarot_card_buffs
    (tarot_name, icon_filename, back_filename, effect_type, effect_value, effect_text, buff_duration, rarity)
VALUES
    ("The Confessor's Mark", 'TempTarot_TheConfessorsMark.png', 'TarotBack_TheConfessorsMark.png', 'score_multiplier',  1.50, 'Boosts post popularity 1.5x',        1440, 1),
    ('Whispered Truth',      'TempTarot_WhisperedTruth.png',    'TarotBack_WhisperedTruth.png',    'vote_weight',       2.00, 'True votes count double',             720, 2),
    ('Veil of Silence',      'TempTarot_VeilOfSilence.png',     'TarotBack_VeilOfSilence.png',     'hide_false_votes',  NULL, 'Hides false vote count',             1440, 2),
    ('Rite of Remaining',    'TempTarot_RiteOfRemaining.png',   'TarotBack_RiteOfRemaining.png',   'pin_position',      NULL, 'The crypt does not bury what it has chosen to remember', 1440, 1),
    ('Ashes to Ashes',       'TempTarot_AshesToAshes.png',      'TarotBack_AshesToAshes.png',      'reset_false_votes', NULL, 'Resets accumulated false votes',     NULL, 3),
    ('The Unblinking Eye',   'TempTarot_TheUnblinkingEye.png',  'TarotBack_TheUnblinkingEye.png',  'piece_drop_rate',   2.00, 'Doubles tarot piece drop rate',       360, 2),
    ('The Toll',             'TempTarot_TheToll.png',           'TarotBack_TheToll.png',           'voter_reward',      1.00, 'None pass judgement here without being paid', 1440, 2),
    ('Second Chance',        'TempTarot_SecondChance.png',      'TarotBack_SecondChance.png',      'reset_ratio',       NULL, 'Resets vote ratio to 0-0',           NULL, 3),
    ("Fortune's Favour",     'TempTarot_FortunesFavour.png',    'TarotBack_FortunesFavour.png',    'vote_trickle',      1.00, '+1 true vote per hour passively',     720, 2),
    ('The Hollow Choir',     'TempTarot_TheHollowChoir.png',    'TarotBack_TheHollowChoir.png',    'feed_priority',     NULL, 'Boosts feed priority without votes', 1440, 3);