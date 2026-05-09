flowchart TD
    A[User opens system] --> B{Login?}
    B -->|No| C[Auth page]
    C --> D[Enter email and password]
    D --> E{Credentials valid?}
    E -->|No| C
    E -->|Yes| F{Role detected}

    F --> G[Admin Dashboard]
    F --> H[Coordinator Dashboard]
    F --> I[Student Portal]
    F --> J[Partner Company Portal]

    G --> G1[Create coordinator accounts]
    G --> G2[Create partner company accounts]
    G --> G3[View totals and charts]
    G --> G4[Manage users]
    G --> G5[Review email logs]

    H --> H1[Create student account]
    H1 --> H2[Upload COR]
    H2 --> H3[Generate temporary password]
    H --> H4[Enroll student to company]
    H4 --> H5[Set start date end date required hours]
    H5 --> H6[Send student enrollment email]
    H5 --> H7[Send company deployment email]
    H --> H8[Monitor students and reports]
    H --> H9[Reset student password]

    I --> I0{First login?}
    I0 -->|Yes| I1[Force password change]
    I0 -->|No| I2[Open dashboard]
    I1 --> I2
    I2 --> I3[View enrollment details]
    I2 --> I4[Submit daily time record]
    I4 --> I5[Compute rendered hours]
    I5 --> I6{Required hours reached?}
    I6 -->|Yes| I7[Mark OJT completed]
    I6 -->|No| I2
    I2 --> I8[Submit weekly report]
    I2 --> I9[View uploaded documents]

    J --> J1[View deployed students]
    J --> J2[Review daily time records]
    J --> J3[Submit final evaluation]

    H6 --> K[Log email result to email_logs]
    H7 --> K
    H9 --> K

    G3 --> L[(users)]
    H1 --> M[(students)]
    H4 --> N[(ojt_enrollments)]
    I4 --> O[(daily_time_records)]
    I8 --> P[(weekly_reports)]
    J3 --> Q[(evaluations)]
    K --> R[(email_logs)]

    G2 --> S[(partner_companies)]
    G1 --> L
    G2 --> S
    H1 --> L
    H4 --> S
    H4 --> M

    # admin@ama.edu.ph / Admin@123