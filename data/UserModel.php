<?php

class UserModel
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function verifyCredential(string $username, string $password): array
    {
        $stmt = $this->conn->prepare(
            'SELECT username, password, role, fullname, refID FROM user WHERE username = ?'
        );
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows !== 1) {
            return ['status' => 'Invalid'];
        }

        $user = $result->fetch_assoc();
        if (!password_verify($password, $user['password'])) {
            return ['status' => 'Invalid'];
        }

        return [
            'status' => 'Valid',
            'username' => $user['username'],
            'role' => strtolower(trim($user['role'])),
            'fullname' => $user['fullname'],
            'refID' => $user['refID'] ?? null,
        ];
    }

    public function usernameExists(string $username): bool
    {
        $stmt = $this->conn->prepare('SELECT username FROM user WHERE username = ?');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }

    public function register(array $details): bool
    {
        $password = password_hash($details['password'], PASSWORD_DEFAULT);
        $stmt = $this->conn->prepare(
            'INSERT INTO user (username, password, email, role, fullname, refID) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $refID = $details['refID'] ?? null;
        $stmt->bind_param(
            'ssssss',
            $details['username'],
            $password,
            $details['email'],
            $details['role'],
            $details['fullname'],
            $refID
        );
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function updateRefId(string $username, ?string $refID): void
    {
        $stmt = $this->conn->prepare('UPDATE user SET refID = ? WHERE username = ?');
        $stmt->bind_param('ss', $refID, $username);
        $stmt->execute();
        $stmt->close();
    }
}
