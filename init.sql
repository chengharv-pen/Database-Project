--  Create Members Relation
CREATE TABLE Members (
	MemberID INT PRIMARY KEY AUTO_INCREMENT,
	Password VARCHAR(255) NOT NULL,
	Username VARCHAR(255) UNIQUE NOT NULL,
	FirstName VARCHAR(255) NOT NULL,
	LastName VARCHAR(255) NOT NULL,
	Pseudonym VARCHAR(255),
	Email VARCHAR(255) UNIQUE NOT NULL,
	Address TEXT,
	DOB DATE NOT NULL,
	DateJoined DATE NOT NULL,	
	Warnings INT DEFAULT 0,
	Suspensions INT DEFAULT 0,
	Fines INT DEFAULT 0,
	Privilege ENUM('Administrator', 'Senior', 'Junior') DEFAULT 'Junior',
	Status ENUM('Active', 'Inactive', 'Suspended') DEFAULT 'Active',
AccountType ENUM('Real-person', 'Business'),
	LastLogin DATETIME,
	PrivateInformation TEXT,
	PublicInformation TEXT,
	GroupVisibilitySettings TEXT,
	ProfileVisibilitySettings TEXT,
	NeedsPasswordChange BOOLEAN DEFAULT TRUE, 
NeedsUsernameChange BOOLEAN DEFAULT TRUE,
Profession VARCHAR(255), -- New field for profession 
Region VARCHAR(255), -- New field for region 
Interests TEXT -- New field for interests
);

--  Create BlockedMembers Relation
CREATE TABLE BlockedMembers (
	BlockerID INT NOT NULL,
	BlockedID INT NOT NULL,
	BlockedDate DATETIME DEFAULT CURRENT_TIMESTAMP,
	Reason TEXT,
	PRIMARY KEY (BlockerID, BlockedID),
	FOREIGN KEY (BlockerID) REFERENCES Members(MemberID) ON DELETE CASCADE,
	FOREIGN KEY (BlockedID) REFERENCES Members(MemberID) ON DELETE CASCADE
);

--  Create PromotionRequests Relation
CREATE TABLE PromotionRequests (
    RequestID INT AUTO_INCREMENT PRIMARY KEY,
    MemberID INT NOT NULL,
    Status ENUM('pending', 'approved', 'denied') DEFAULT 'pending',
    RequestDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (MemberID) REFERENCES Members(MemberID)
);

--  Create Groups Relation
CREATE TABLE `Groups` (
	GroupID INT PRIMARY KEY AUTO_INCREMENT,
	GroupName VARCHAR(255) NOT NULL,
	OwnerID INT NOT NULL,
	CreationDate DATETIME NOT NULL,
	GroupType ENUM('Family', 'Friends', 'Colleagues', 'Other'),
InterestCategory VARCHAR(255), -- New field for group interest category 
Region VARCHAR(255), -- New field for group region
	FOREIGN KEY (OwnerID) REFERENCES Members(MemberID) ON DELETE CASCADE
);

--  Create GroupMembers Relation
CREATE TABLE GroupMembers (
	GroupMemberID INT PRIMARY KEY AUTO_INCREMENT,
	GroupID INT NOT NULL,
	MemberID INT NOT NULL,
	Role ENUM('Admin', 'Member') DEFAULT 'Member',
	DateAdded DATE NOT NULL,
	FOREIGN KEY (GroupID) REFERENCES `Groups`(GroupID) ON DELETE CASCADE,
	FOREIGN KEY (MemberID) REFERENCES Members(MemberID) ON DELETE CASCADE,
	UNIQUE (GroupID, MemberID)
);







--  Create GroupJoinRequests Relation
CREATE TABLE GroupJoinRequests (
	RequestID INT PRIMARY KEY AUTO_INCREMENT, 
	GroupID INT NOT NULL,
	MemberID INT NOT NULL,
	RequestDate DATE NOT NULL DEFAULT (CURDATE()),
	Status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
	ReviewedBy INT,
	ReviewDate DATE,
	ReviewComments TEXT,
	FOREIGN KEY (GroupID) REFERENCES `Groups`(GroupID) ON DELETE CASCADE,
	FOREIGN KEY (MemberID) REFERENCES Members(MemberID) ON DELETE CASCADE,
	FOREIGN KEY (ReviewedBy) REFERENCES Members(MemberID) ON DELETE SET NULL
);

--  Create Posts Relation
CREATE TABLE Posts (
	PostID INT PRIMARY KEY AUTO_INCREMENT,
	AuthorID INT NOT NULL,
	Content TEXT,
	CommentsCount INT DEFAULT 0,
	PostDate DATETIME NOT NULL,
	VisibilitySettings ENUM('Public', 'Group', 'Private') DEFAULT 'Group',
	FOREIGN KEY (AuthorID) REFERENCES Members(MemberID) ON DELETE CASCADE
);

--  Create PostGroups Relation
CREATE TABLE PostGroups (
    PostID INT,
    GroupID INT,
    PRIMARY KEY (PostID, GroupID),
    FOREIGN KEY (PostID) REFERENCES Posts(PostID) ON DELETE CASCADE,
    FOREIGN KEY (GroupID) REFERENCES `Groups`(GroupID) ON DELETE CASCADE
);

--  Create PostMedia Relation
CREATE TABLE PostMedia (
	MediaID INT PRIMARY KEY AUTO_INCREMENT,
	PostID INT NOT NULL,
	MediaType ENUM('Image', 'Video') NOT NULL,
	MediaURL VARCHAR(255) NOT NULL,
	UploadedAt DATETIME NOT NULL,
	FOREIGN KEY (PostID) REFERENCES Posts(PostID) ON DELETE CASCADE
);

-- Table to track likes
CREATE TABLE PostLikes (
    PostID INT,
    UserID INT,
    PRIMARY KEY (PostID, UserID),
    FOREIGN KEY (PostID) REFERENCES Posts(PostID) ON DELETE CASCADE,
    FOREIGN KEY (UserID) REFERENCES Members(MemberID) ON DELETE CASCADE
);

-- Table to track dislikes
CREATE TABLE PostDislikes (
    PostID INT,
    UserID INT,
    PRIMARY KEY (PostID, UserID),
    FOREIGN KEY (PostID) REFERENCES Posts(PostID) ON DELETE CASCADE,
    FOREIGN KEY (UserID) REFERENCES Members(MemberID) ON DELETE CASCADE
);

--  Create Comments Relation
CREATE TABLE Comments (
	CommentID INT PRIMARY KEY AUTO_INCREMENT,
	PostID INT NOT NULL,
	AuthorID INT NOT NULL,
	Content TEXT NOT NULL,
	CreationDate DATETIME NOT NULL,
	FOREIGN KEY (PostID) REFERENCES Posts(PostID) ON DELETE CASCADE,
	FOREIGN KEY (AuthorID) REFERENCES Members(MemberID) ON DELETE CASCADE
);

--  Create Relationships Relation
CREATE TABLE Relationships (
	RelationshipID INT PRIMARY KEY AUTO_INCREMENT,
	SenderMemberID INT NOT NULL,
	ReceiverMemberID INT NOT NULL,
	RelationshipType ENUM('Family', 'Friend', 'Colleague') NOT NULL,
	CreationDate DATE NOT NULL,
	Status ENUM('Active', 'Pending') DEFAULT 'Active',
	FOREIGN KEY (SenderMemberID) REFERENCES Members(MemberID) ON DELETE CASCADE,
	FOREIGN KEY (ReceiverMemberID) REFERENCES Members(MemberID) ON DELETE CASCADE,
	UNIQUE (SenderMemberID, ReceiverMemberID)
);



--  Create Messages Relation
CREATE TABLE Messages (
	MessageID INT PRIMARY KEY AUTO_INCREMENT,
	SenderID INT NOT NULL,
	ReceiverID INT NOT NULL,
	Content TEXT NOT NULL,
	DataSent DATETIME NOT NULL,
	ReadStatus ENUM('Unread', 'Read', 'Deleted') DEFAULT 'Unread',
	FOREIGN KEY (SenderID) REFERENCES Members(MemberID) ON DELETE CASCADE,
	FOREIGN KEY (ReceiverID) REFERENCES Members(MemberID) ON DELETE CASCADE
);


--  Create Email Relation
CREATE TABLE Email (
	EmailID INT PRIMARY KEY AUTO_INCREMENT,
	SenderID INT NOT NULL,
	ReceiverID INT NOT NULL,
	Subject VARCHAR(255),
	Body TEXT,
	DateSent DATETIME,
	ReadStatus TINYINT(1) DEFAULT 0 NOT NULL,
	FOREIGN KEY (SenderID) REFERENCES Members(MemberID),
	FOREIGN KEY (ReceiverID) REFERENCES Members(MemberID)
);

-- Create Events relation
CREATE TABLE Events (
	EventID INT PRIMARY KEY AUTO_INCREMENT,
	GroupID INT NOT NULL,
	EventTitle VARCHAR(255) NOT NULL,
	EventDescription TEXT,
	EventCreatorID INT NOT NULL,
	EventStatus ENUM('Pending', 'Scheduled', 'Cancelled') DEFAULT 'Pending', -- Event status
	CreationDate DATETIME DEFAULT CURRENT_TIMESTAMP,
	FOREIGN KEY (GroupID) REFERENCES `Groups`(GroupID) ON DELETE CASCADE,
	FOREIGN KEY (EventCreatorID) REFERENCES Members(MemberID) ON DELETE CASCADE
);





-- Create EventVotingOptions relation
CREATE TABLE EventVotingOptions (
    	OptionID INT PRIMARY KEY AUTO_INCREMENT,
    	EventID INT NOT NULL,
    	OptionDate DATE,  -- Date of the event
    	OptionTime TIME,  -- Time of the event
   	 OptionPlace VARCHAR(255),  -- Location of the event
    	IsSuggestedByMember BOOLEAN DEFAULT FALSE,
    	FOREIGN KEY (EventID) REFERENCES Events(EventID) ON DELETE CASCADE
);



-- Create EventVotes relation
CREATE TABLE EventVotes (
	VoteID INT PRIMARY KEY AUTO_INCREMENT,
	EventID INT NOT NULL,
	MemberID INT NOT NULL,
	OptionID INT NOT NULL,
	VoteDate DATETIME DEFAULT CURRENT_TIMESTAMP,  -- Timestamp of vote
	FOREIGN KEY (EventID) REFERENCES Events(EventID) ON DELETE CASCADE,
	FOREIGN KEY (MemberID) REFERENCES Members(MemberID) ON DELETE CASCADE,
	FOREIGN KEY (OptionID) REFERENCES EventVotingOptions(OptionID) ON DELETE CASCADE,
	UNIQUE (EventID, MemberID, OptionID)  -- Prevents multiple votes by the same member for the same option
);

-- Create GiftExchange relation
CREATE TABLE GiftExchange (
	GiftExchangeID INT PRIMARY KEY AUTO_INCREMENT,
	GroupID INT NOT NULL, -- To track which group or family is organizing this
	EventName VARCHAR(255) NOT NULL, -- e.g., "Christmas Secret Santa"
	EventDate DATETIME NOT NULL, -- The date of the exchange event
	MaxBudget DECIMAL(10, 2), -- Max budget for the gift
	Status ENUM('Pending', 'Ongoing', 'Completed') DEFAULT 'Pending', -- Status of the gift exchange
	CreationDate DATETIME DEFAULT CURRENT_TIMESTAMP,
	FOREIGN KEY (GroupID) REFERENCES `Groups`(GroupID) ON DELETE CASCADE
);






-- Create GiftExchangeParticipants relation
CREATE TABLE GiftExchangeParticipants (
	ParticipantID INT PRIMARY KEY AUTO_INCREMENT,
	GiftExchangeID INT NOT NULL,
	MemberID INT NOT NULL,
	AssignedToMemberID INT, -- The person the member will buy a gift for (Secret Santa pair)
	GiftPreference TEXT, -- Optional: Members can specify gift preferences
	ExchangeStatus ENUM('Assigned', 'Gift Purchased', 'Gift Given', 'Completed') DEFAULT 'Assigned', -- Status of the exchange for each participant
PaymentAmount DECIMAL(10, 2) DEFAULT 0,
	FOREIGN KEY (GiftExchangeID) REFERENCES GiftExchange(GiftExchangeID) ON DELETE CASCADE,
	FOREIGN KEY (MemberID) REFERENCES Members(MemberID) ON DELETE CASCADE,
	FOREIGN KEY (AssignedToMemberID) REFERENCES Members(MemberID) ON DELETE SET NULL, -- If gift assignment is not yet made
	UNIQUE (GiftExchangeID, MemberID)
);

-- Create Warnings Relation
CREATE TABLE Warnings (
	WarningID INT AUTO_INCREMENT PRIMARY KEY,
	MemberID INT,
	PostID INT,
	MessageID INT,
	CommentID INT,
	EmailID INT,
	Reason VARCHAR(255) NOT NULL,
	CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	IssuedBy INT,
	WarningType ENUM('Direct', 'Post', 'Comment', 'Message', 'EmailMessage') NOT NULL,
	FOREIGN KEY (MemberID) REFERENCES Members(MemberID) ON DELETE CASCADE,
	FOREIGN KEY (PostID) REFERENCES Posts(PostID) ON DELETE CASCADE,
	FOREIGN KEY (MessageID) REFERENCES Messages(MessageID) ON DELETE CASCADE,
	FOREIGN KEY (CommentID) REFERENCES Comments(CommentID) ON DELETE CASCADE,
	FOREIGN KEY (EmailID) REFERENCES Email(EmailID) ON DELETE CASCADE,
	FOREIGN KEY (IssuedBy) REFERENCES Members(MemberID) ON DELETE SET NULL
);




--  Create Payments Relation
CREATE TABLE Payments (
	PaymentID INT PRIMARY KEY AUTO_INCREMENT,
	MemberID INT NOT NULL,
	Amount DECIMAL(10, 2) NOT NULL,
	PaymentDate DATE NOT NULL,
	Description TEXT,
	FOREIGN KEY (MemberID) REFERENCES Members(MemberID) ON DELETE CASCADE
);

--  Create Suspensions Relation
CREATE TABLE Suspensions (
    SuspensionID INT PRIMARY KEY AUTO_INCREMENT,
    MemberID INT,
    StartDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    EndDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Reason VARCHAR(255),
    IssuedBy INT,  -- The member who issued the suspension, probably an admin
    FOREIGN KEY (MemberID) REFERENCES Members(MemberID) ON DELETE CASCADE,
    FOREIGN KEY (IssuedBy) REFERENCES Members(MemberID) ON DELETE SET NULL
);

-- Create Reports Relation
CREATE TABLE Reports (
ReportID INT AUTO_INCREMENT PRIMARY KEY,
PostID INT,
MessageID INT,
CommentID INT,
EmailID INT,
ReportType ENUM('Post', 'Comment', 'Message', 'EmailMessage') NOT NULL,
AdminID INT NOT NULL,  -- Administrator handling the report
ReportStatus ENUM('Pending', 'Approved', 'Disapproved') DEFAULT 'Pending',
ResolutionDetails TEXT,  -- Details on how the report was resolved
CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
ResolvedAt TIMESTAMP NULL DEFAULT NULL,
FOREIGN KEY (AdminID) REFERENCES Members(MemberID) ON DELETE CASCADE,
FOREIGN KEY (PostID) REFERENCES Posts(PostID) ON DELETE CASCADE, 
FOREIGN KEY (MessageID) REFERENCES Messages(MessageID) ON DELETE CASCADE,
FOREIGN KEY (CommentID) REFERENCES Comments(CommentID) ON DELETE CASCADE,
FOREIGN KEY (EmailID) REFERENCES Email(EmailID) ON DELETE CASCADE
);


--  Create Notifications Relation
CREATE TABLE Notifications (
	NotificationID INT PRIMARY KEY AUTO_INCREMENT,
	MemberID INT NOT NULL,
	Content TEXT NOT NULL,
	NotificationDate DATETIME NOT NULL,
	Type ENUM('Unread', 'Read') DEFAULT 'Unread',
	FOREIGN KEY (MemberID) REFERENCES Members(MemberID) ON DELETE CASCADE
);

--  Insert a default Member in Members with Username ‘admin’ and Password ‘admin’
INSERT INTO Members (
	Password,
	Username,
	FirstName,
	LastName,
	Pseudonym,
	Email,
	Address,
	DOB,
	DateJoined,
	Privilege,
	AccountType,
	Status
)
VALUES (
	'$2y$10$AjecbbHv73ewhzCL0tbKueen1iEa8GG7CF0C86WWORP7ymAM1AEZe',
	'admin',
	'Admin',
	'User',
	NULL,
	'admin@protonmail.com',
	NULL,
	'1970-01-01',
	CURDATE(),
	'Administrator',
	'Real-person',
	'Inactive'
);

--  Insert Members (EVERY PASSWORD IS JUST ‘admin’ BUT ENCRYPTED)
INSERT INTO Members (MemberID, Password, Username, FirstName, LastName, Pseudonym, Email, Address, DOB, DateJoined, Warnings, Suspensions, Fines, Privilege, Status, AccountType, LastLogin, PrivateInformation, PublicInformation, GroupVisibilitySettings, ProfileVisibilitySettings, NeedsPasswordChange, NeedsUsernameChange, Profession, Region, Interests) 
VALUES 
(2, '$2y$10$qPiIbWr2DUQyQAWapi9Wqu94xX92lsCFvG4psuoY4N1qyLQLC8spa', 'admin123', 'Admin', 'User', '', 'admin123@protonmail.com', '', '1970-01-01', '2024-12-05', 0, 0, 0, 'Administrator', 'Inactive', 'Real-person', '2024-12-06 15:16:06', '', '', 'Public', 'Public', 0, 0, '', '', ''),
(3, '$2y$10$l6YhXpjFVYmPUva4WGA5DeB61q0Wo4yb9H/2uTZNvDkzwg1q5be6y', 'admin12', 'Admin', 'User', '', 'admin12@protonmail.com', '', '1970-01-01', '2024-12-05', 0, 0, 0, 'Administrator', 'Inactive', 'Real-person', '2024-12-06 15:17:03', '', '', '', '', 0, 0, '', '', ''),
(4, '$2y$10$ANaZZOTsMOkpE3DM/u7wOeGyAgQl0U0bknYou4arGQUR/CRg53nAC', 'admin1', 'Admin', 'User', '', 'admin1@protonmail.com', '', '1970-01-01', '2024-12-05', 0, 0, 0, 'Administrator', 'Inactive', 'Real-person', '2024-12-06 14:59:29', '', '', '', '', 0, 0, '', '', ''),
(5, '$2y$10$tVW4GdZZrNkSsVRisBhFlem4LaO9voPoKFMyW8/kEsTWTI3NIqEAm', 'j1', 'j', '1', '', 'j1@protonmail.com', '', '1997-04-23', '2024-12-05', 0, 0, 0, 'Junior', 'Inactive', 'Real-person', '2024-12-06 15:08:15', '', '', '', '', 0, 0, '', '', ''),
(6, '$2y$10$e1lX3NrmDoAMcd/zli72LuR4c58zHSTLjM7m0AvKRWpSMnaipCirq', 'j2', 'j', '2', '', 'j2@protonmail.com', '', '1923-09-02', '2024-12-05', 0, 0, 0, 'Junior', 'Inactive', 'Real-person', '2024-12-06 00:09:30', '', '', '', '', 0, 0, '', '', ''),
(7, '$2y$10$wHo93MQhMgdHKaQUYv9Tv.f4lEBNuSpFQZ.gmOECgTqgTkTpE.UWa', 's1', 's', '1', '', 's1@protonmail.com', '', '1923-03-08', '2024-12-05', 0, 0, 0, 'Senior', 'Inactive', 'Real-person', '2024-12-06 00:26:28', '', '', '', '', 0, 0, '', '', ''),
(8, '$2y$10$fSC9RXPNqMB0/G067Se0PuhzWQzTg48Sf42ezVMWAhYca.Ps4QcB.', 's2', 's', '2', '', 's2@protonmail.com', '', '1958-02-01', '2024-12-05', 0, 0, 0, 'Senior', 'Inactive', 'Real-person', '2024-12-06 15:07:56', '', '', '', '', 0, 0, '', '', ''),
(9, '$2y$10$YyczqZsdcR/zX61pUXSMruU3pLxp8XP5Rd0No46ozS.HeIIc7fQvq', 's3', 's', '3', '', 's3@protonmail.com', '', '1923-03-23', '2024-12-05', 0, 0, 0, 'Senior', 'Inactive', 'Real-person', '2024-12-06 00:11:07', '', '', '', '', 0, 0, '', '', ''),
(10, '$2y$10$AcMRB2HMa66dmB/82WpZmOsQ7YyQZWYUvdsI1U8F63h6bQ2G17Yra', 'j3', 'j', '3', '', 'j3@protonmail.com', '', '1932-02-10', '2024-12-05', 3, 1, 0, 'Junior', 'Suspended', 'Real-person', '2024-12-06 00:09:45', '', '', '', '', 0, 0, '', '', ''),
(11, '$2y$10$T3MqZ47C5V.KqiKOPzlvA.oT8caUR2RsHmIiO8wNqLpo6gY6KKXIi', 'test-fraud', 'test', 'fraud', '', 'test-fraud@protonmail.com', '', '2004-03-31', '2024-12-06', 3, 1, 0, 'Junior', 'Suspended', 'Real-person', NULL, '', '', '', '', 0, 0, '', '', ''),
(12, '$2y$10$7Knfo3iSP6.0en2D.rJ/NOp0Q2e4Lik/Gx2BGARY6F8QTmJ/R.jha', 'test-fraud-business', 'test', 'fraud-business', '', 'test-fraud-business@protonmail.com', '', '2004-03-01', '2024-12-06', 6, 2, 4, 'Junior', 'Suspended', 'Business', '2024-12-06 01:28:10', '', '', '', '', 0, 0, '', '', ''),
(13, '$2y$10$ni3lhNx/Epz5c/6yCTUwgudr86FJLPOfJAqVBRtZk16NhXtL.lisG', 'new-user', 'new', 'user', '', 'new-user@protonmail.com', '', '2004-12-23', '2024-12-06', 0, 0, 0, 'Junior', 'Inactive', 'Real-person', '2024-12-06 15:03:01', '', '', '', '', 0, 0, '', '', '');

--  Insert BlockedMembers (NOTHING)


--  Insert PromotionRequests
INSERT INTO PromotionRequests (RequestID, MemberID, Status, RequestDate) VALUES (1, 7, 'denied', '2024-12-05 22:48:33');

--  Insert Groups
INSERT INTO `Groups` (GroupID, GroupName, OwnerID, CreationDate, GroupType, InterestCategory, Region) 
VALUES 
(3, 'Admin Group', 2, '2024-12-05 22:56:58', 'Family', 'admin', 'Admin'),
(5, 'Senior Group', 8, '2024-12-05 23:23:36', 'Friends', 'Senior', 'Senior'),
(6, 'Junior Group', 2, '2024-12-05 23:36:34', 'Friends', 'Junior', 'Junior'),
(9, 'Test Group', 8, '2024-12-06 15:03:48', 'Family', '', '');

--  Insert GroupMembers
INSERT INTO GroupMembers (GroupMemberID, GroupID, MemberID, Role, DateAdded)
VALUES 
(3, 3, 2, 'Admin', '2024-12-05'),
(5, 3, 3, 'Member', '2024-12-05'),
(7, 5, 8, 'Admin', '2024-12-05'),
(8, 5, 9, 'Member', '2024-12-05'),
(9, 6, 2, 'Admin', '2024-12-05'),
(10, 6, 5, 'Admin', '2024-12-05'),
(11, 6, 6, 'Member', '2024-12-05'),
(12, 6, 10, 'Member', '2024-12-05'),
(13, 5, 2, 'Admin', '2024-12-05'),
(14, 5, 7, 'Member', '2024-12-05'),
(17, 9, 8, 'Admin', '2024-12-06'),
(19, 9, 5, 'Member', '2024-12-06');	

--  Insert GroupJoinRequests
INSERT INTO GroupJoinRequests (RequestID, GroupID, MemberID, RequestDate, Status, ReviewedBy, ReviewDate, ReviewComments)
VALUES 
(1, 3, 5, '2024-12-05', 'Approved', 2, '2024-12-06', 'Sample Text'),
(2, 3, 3, '2024-12-05', 'Approved', 2, '2024-12-06', 'Sample Text'),
(3, 5, 9, '2024-12-05', 'Approved', 8, '2024-12-06', 'Sample Text'),
(4, 6, 5, '2024-12-05', 'Approved', 2, '2024-12-06', 'Sample Text'),
(5, 6, 6, '2024-12-05', 'Approved', 5, '2024-12-06', 'Sample Text'),
(6, 6, 10, '2024-12-05', 'Approved', 5, '2024-12-06', 'Sample Text'),
(7, 5, 2, '2024-12-05', 'Approved', 8, '2024-12-06', 'Sample Text'),
(8, 5, 7, '2024-12-05', 'Approved', 8, '2024-12-06', 'Sample Text'),
(9, 9, 5, '2024-12-06', 'Approved', 2, '2024-12-06', 'Sample Text'),
(10, 9, 5, '2024-12-06', 'Approved', 2, '2024-12-06', 'Sample Text');

--  Insert Relationships
INSERT INTO Relationships (RelationshipID, SenderMemberID, ReceiverMemberID, RelationshipType, CreationDate, Status)
VALUES 
(1, 8, 7, 'Friend', '2024-12-05', 'Active'),
(2, 8, 9, 'Friend', '2024-12-05', 'Active'),
(3, 8, 2, 'Friend', '2024-12-05', 'Active'),
(4, 8, 3, 'Friend', '2024-12-05', 'Active'),
(5, 5, 6, 'Friend', '2024-12-05', 'Active'),
(6, 5, 10, 'Friend', '2024-12-05', 'Active'),
(7, 5, 2, 'Friend', '2024-12-05', 'Active'),
(8, 5, 3, 'Friend', '2024-12-05', 'Active'),
(9, 9, 7, 'Friend', '2024-12-05', 'Active'),
(11, 6, 10, 'Friend', '2024-12-05', 'Active'),
(13, 2, 3, 'Friend', '2024-12-06', 'Active');

--  Insert Posts
INSERT INTO Posts (PostID, AuthorID, Content, CommentsCount, PostDate, VisibilitySettings)
VALUES 
(1, 2, 'Admin Group Only Post', 0, '2024-12-05 23:52:44', 'Group'),
(2, 2, 'Junior Group Only Post', 0, '2024-12-05 23:52:54', 'Group'),
(3, 2, 'Senior Group Only Post', 0, '2024-12-05 23:53:03', 'Group'),
(8, 5, 'j1 Post (Private)', 0, '2024-12-06 00:04:53', 'Private'),
(10, 2, 'admin123 Post (Private)', 0, '2024-12-06 00:09:02', 'Private'),
(11, 3, 'admin12 Post (Private)', 0, '2024-12-06 00:09:24', 'Private'),
(12, 6, 'j2 Post (Private)', 0, '2024-12-06 00:09:39', 'Private'),
(13, 10, 'j3 Post (Private)', 0, '2024-12-06 00:09:56', 'Private'),
(14, 7, 's1 Post (Private)', 0, '2024-12-06 00:10:25', 'Private'),
(15, 8, 's2 Post (Private)', 0, '2024-12-06 00:10:53', 'Private'),
(16, 9, 's3 Post (Private)', 0, '2024-12-06 00:11:20', 'Private'),
(17, 2, 'Sample Text (Public)', 0, '2024-12-06 00:15:34', 'Public'),
(18, 2, 'Image Post (Public)', 2, '2024-12-06 00:19:01', 'Public'),
(19, 2, 'Video Post (Public)', 0, '2024-12-06 00:19:41', 'Public'),
(23, 8, 'public post from s2', 0, '2024-12-06 15:05:32', 'Public'),
(24, 8, 'image post', 0, '2024-12-06 15:05:59', 'Public'),
(25, 8, 'video post', 0, '2024-12-06 15:06:11', 'Public'),
(26, 8, 'group post', 0, '2024-12-06 15:07:08', 'Group');



--  Insert PostGroups
INSERT INTO PostGroups (PostID, GroupID)
VALUES 
(1, 3),
(3, 5),
(2, 6),
(26, 9);

--  Insert PostMedia
INSERT INTO PostMedia (MediaID, PostID, MediaType, MediaURL, UploadedAt)
VALUES 
(1, 18, 'Image', '../uploads/media_67528945cedb33.62488687.png', '2024-12-06 00:19:01'),
(2, 19, 'Video', '../uploads/media_6752896d8b88b7.13569071.mkv', '2024-12-06 00:19:41'),
(5, 24, 'Image', '../uploads/media_67535927acfe02.45653758.jpg', '2024-12-06 15:05:59'),
(6, 25, 'Video', '../uploads/media_67535933a5acb0.21770493.mkv', '2024-12-06 15:06:11');

--  Insert PostLikes
INSERT INTO PostLikes (PostID, UserID)
VALUES 
(17, 5),
(18, 5),
(17, 7),
(15, 9),
(17, 12);

--  Insert PostDislikes (NOTHING)


--  Insert Comments
INSERT INTO Comments (CommentID, PostID, AuthorID, Content, CreationDate)
VALUES 
(2, 18, 7, 'hello from s1', '2024-12-06 00:20:59'),
(4, 18, 8, 'hello from j1', '2024-12-06 00:25:47');

--  Insert Messages
INSERT INTO Messages (MessageID, SenderID, ReceiverID, Content, DataSent, ReadStatus)
VALUES 
(1, 2, 3, 'hello admin12', '2024-12-06 00:14:56', 'Unread'),
(2, 3, 2, 'hello admin123', '2024-12-06 00:15:12', 'Unread'),
(3, 2, 3, 'h', '2024-12-06 00:28:19', 'Unread'),
(4, 2, 3, 'e', '2024-12-06 00:28:21', 'Unread'),
(5, 2, 3, 'l', '2024-12-06 00:28:22', 'Unread'),
(6, 2, 3, 'l', '2024-12-06 00:28:23', 'Unread'),
(7, 2, 3, 'o', '2024-12-06 00:28:24', 'Unread'),
(8, 2, 3, 'test chat', '2024-12-06 15:10:17', 'Unread'),
(9, 3, 2, 'test chat 2\r\n', '2024-12-06 15:10:32', 'Unread');

--  Insert Email
INSERT INTO Email (EmailID, SenderID, ReceiverID, Subject, Body, DateSent, ReadStatus)
VALUES
(1, 2, 3, 'TEST SEND EMAIL', 'TEST SEND EMAIL\r\n\r\n(This should be in admin12''s Inbox.)', '2024-12-06 00:29:54', 1),
(2, 3, 2, 'TEST SEND EMAIL', 'TEST SEND EMAIL\r\n\r\n(This should be in admin123''s Inbox.)', '2024-12-06 00:30:39', 1),
(3, 2, 3, 'popup test', 'popup test', '2024-12-06 13:52:51', 1),
(4, 2, 3, 'demo sent email', 'a', '2024-12-06 15:14:01', 1);

--  Insert Events
INSERT INTO Events (EventID, GroupID, EventTitle, EventDescription, EventCreatorID, EventStatus, CreationDate)
VALUES
(1, 3, 'Admin Event (Expired)', 'Admin Event (Expired)', 2, 'Scheduled', '2024-12-06 00:33:28'),
(2, 3, 'Admin Event (Upcoming)', 'Admin Event (Upcoming)', 2, 'Scheduled', '2024-12-06 00:46:37'),
(3, 3, 'Admin Event (Upcoming 2)', 'Admin Event (Upcoming 2)', 2, 'Scheduled', '2024-12-06 01:16:33'),
(4, 3, 'Test Event', 'sample text', 2, 'Scheduled', '2024-12-06 15:11:20');

--  Insert EventVotingOptions
INSERT INTO EventVotingOptions (OptionID, EventID, OptionDate, OptionTime, OptionPlace, IsSuggestedByMember)
VALUES
(1, 1, '2024-12-01', '00:00:00', 'Test Street 12345', 0),
(2, 1, '2024-12-01', '00:15:00', 'Test Street 1234', 1),
(3, 2, '3000-01-01', '00:48:00', 'Upcoming Street 111', 1),
(4, 2, '3000-02-28', '00:49:00', 'Upcoming Street 111', 0),
(5, 3, '2050-10-02', '01:18:00', 'Upcoming Street 222', 0),
(6, 4, '2999-12-31', '15:11:00', 'Test Street', 0),
(7, 4, '2999-12-30', '15:11:00', 'Test 12345', 1);

--  Insert EventVotes
INSERT INTO EventVotes (VoteID, EventID, MemberID, OptionID, VoteDate)
VALUES
(1, 1, 3, 2, '2024-12-06 00:35:11'),
(2, 1, 2, 2, '2024-12-06 00:35:34'),
(3, 2, 3, 3, '2024-12-06 00:48:02'),
(4, 3, 2, 5, '2024-12-06 01:16:54'),
(5, 4, 3, 7, '2024-12-06 15:12:40'),
(6, 4, 2, 7, '2024-12-06 15:13:00');



--  Insert GiftExchange
INSERT INTO GiftExchange (GiftExchangeID, GroupID, EventName, EventDate, MaxBudget, Status, CreationDate)
VALUES
(1, 3, 'Test Gift Exchange', '2024-12-14 01:20:00', 0.0, 'Completed', '2024-12-06 01:18:37'),
(2, 3, 'Gift Exchange', '2024-12-06 15:14:00', 4000.0, 'Completed', '2024-12-06 15:15:10');

--  Insert GiftExchangeParticipants
INSERT INTO GiftExchangeParticipants (ParticipantID, GiftExchangeID, MemberID, AssignedToMemberID, GiftPreference, ExchangeStatus, PaymentAmount)
VALUES
(1, 1, 2, 3, 'Tie', 'Completed', 5000.0),
(2, 1, 3, 2, 'Ring', 'Completed', 5000.0),
(4, 2, 2, 3, 'Hello', 'Completed', 5000.0),
(5, 2, 3, 2, 'World', 'Completed', 1000.0);

--  Insert Warnings
INSERT INTO Warnings (WarningID, MemberID, PostID, MessageID, CommentID, EmailID, Reason, CreatedAt, IssuedBy, WarningType)
VALUES
(1, 11, NULL, NULL, NULL, NULL, 'test-fraud', '2024-12-06 01:23:55', 2, 'Direct'),
(2, 11, NULL, NULL, NULL, NULL, 'test-fraud', '2024-12-06 01:23:59', 2, 'Direct'),
(3, 11, NULL, NULL, NULL, NULL, 'test-fraud', '2024-12-06 01:24:07', 2, 'Direct'),
(4, 12, NULL, NULL, NULL, NULL, 'business fraud', '2024-12-06 01:25:41', 2, 'Direct'),
(5, 12, NULL, NULL, NULL, NULL, 'business fraud', '2024-12-06 01:25:43', 2, 'Direct'),
(6, 12, NULL, NULL, NULL, NULL, 'business fraud', '2024-12-06 01:25:54', 2, 'Direct'),
(7, 12, NULL, NULL, NULL, NULL, 'business fraud', '2024-12-06 01:26:01', 2, 'Direct'),
(8, 12, NULL, NULL, NULL, NULL, 'business fraud', '2024-12-06 01:26:08', 2, 'Direct'),
(9, 12, NULL, NULL, NULL, NULL, 'business fraud', '2024-12-06 01:32:16', 2, 'Direct'),
(10, 10, NULL, NULL, NULL, NULL, 'warning', '2024-12-06 15:00:56', 2, 'Direct'),
(11, 10, NULL, NULL, NULL, NULL, 'warning', '2024-12-06 15:01:00', 2, 'Direct'),
(12, 10, NULL, NULL, NULL, NULL, 'warning', '2024-12-06 15:01:04', 2, 'Direct');

--  Insert Payments
INSERT INTO Payments (PaymentID, MemberID, Amount, PaymentDate, Description)
VALUES
(4, 12, 200.0, '2024-12-06', 'Fine for excessive warnings (Fine #4)');

--  Insert Suspensions
INSERT INTO Suspensions (SuspensionID, MemberID, StartDate, EndDate, Reason, IssuedBy)
VALUES
(1, 11, '2024-12-06 01:24:07', '2024-12-14 01:24:07', 'Excessive warnings', 2),
(2, 12, '2024-12-06 01:26:08', '2024-12-06 01:27:51', 'Excessive fines (more than 2)', 2),
(3, 12, '2024-12-06 01:32:16', '2025-01-05 01:32:16', 'Excessive fines (more than 2)', 2),
(4, 10, '2024-12-06 15:01:04', '2024-12-14 15:01:04', 'Excessive warnings', 2);
