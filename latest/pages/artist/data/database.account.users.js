  // Configurações
        const itemsPerPage = 10;
        let currentPage = 1;
        let currentEditingId = null;
        let filteredData = [];

        // Dados de exemplo
        const usersData = [
            {
                id: 1,
                account: "user1",
                email: "user1@example.com",
                phone: "+244123456789",
                country: "AO",
                city: "Luanda",
                plan: "premium",
                status: "active",
                created_at: "2023-01-15"
            },
            {
                id: 2,
                account: "user2",
                email: "user2@example.com",
                phone: "+351912345678",
                country: "PT",
                city: "Lisboa",
                plan: "free",
                status: "active",
                created_at: "2023-02-20"
            },
            {
                id: 3,
                account: "user3",
                email: "user3@example.com",
                phone: "+5511987654321",
                country: "BR",
                city: "São Paulo",
                plan: "enterprise",
                status: "suspended",
                created_at: "2023-03-10"
            },
            {
                id: 4,
                account: "user4",
                email: "user4@example.com",
                phone: "+244987654321",
                country: "AO",
                city: "Benguela",
                plan: "premium",
                status: "review",
                created_at: "2023-04-05"
            },
            {
                id: 5,
                account: "user5",
                email: "user5@example.com",
                phone: "+351963852741",
                country: "PT",
                city: "Porto",
                plan: "free",
                status: "active",
                created_at: "2023-05-12"
            }
        ];
