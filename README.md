
# cs79c_final_project
Final Project for cs79c
Stack creation

<img width="665" height="767" alt="final-diagram" src="https://github.com/user-attachments/assets/a2c05601-96eb-4102-900d-83e33ca10398" />

1. Download valest-final-project.yaml. This file will help expedite the process of creating our EC2 instances. The YAML file will work but just make sure you validate the file before uploading. Use the command aws cloudformation validate-template --template-body file://cs79c-stack.yaml in your cli.

2. Open AWS Cloudformation stacks>create stack and Upload the template file.

3. Watch events to confirm the stack creation process is completed.

ALB



1. We will need to create listeners as they were not included in our cloudformation stack.
2. Create an application load balancer, make sure you select the subnets we made within our YAML file as well as our security group. Use the listeners we just created for our ALB.
3. Wait for the health checks to go through to make sure your alb is communicating with your ec2 instances.
4. Make sure to test your ALB with http://<DNS Name>, You will currently receive a 503 error as we have not injected any data into the ec2 instances. 


EC2
1. Navigate to EC2 > Instances, open your first ec2 instance and EC2 Instance Connect.
2. Enter the following commands in both instances:
3. `sudo dnf install -y httpd php`
4. `sudo systemctl enable --now httpd`
   `echo "<h1>WebServer is working</h1>" | sudo tee /var/www/html/index.html`


Volumes
1. Navigate to EC2>Volumes and create a new volume. Make sure you keep track of which instance you’re attaching it to for later as we will be using an s3 bucket to back up the data in this. 
2. We will now be mounting the volume to the ec2 instance, connect to the ec2 instance you attached the volume to and enter the command `lsblk`. You should see the following new volume of 5G.
3. Run the following commands in your instance with the 
`sudo mkfs -t ext4 /dev/nvme1n1` 
`sudo mkdir /mnt/new-volume`
sudo mount /dev/nvme1n1 /mnt/new-volume
4. You can confirm the volume has been mounted by running `lsblk` again.


S3 Bucket
1. First, create a bucket. You will not need to make this public as we will be creating roles for this. 
2. Navigate to IAM Roles, create a new role with the ec2 use case and select AmazonS3FullAccess and a custom policy with s3:ListBucket,PutObject, and GetObject Policies for this role. You will only need to attach the policy to webserver1 as we are only attaching our bucket that instance while the second instance will still continue to server web traffic.
3. Log back into webinstance one and create a test file to make sure our data can be copied to our s3 bucket. Run “sudo echo "test" | sudo tee /mnt/new-volume/test.txt”. This will create and write to our new file in our mounted volume. 
4. Now test this test file with aws s3 cp /mnt/new-volume/test.txt s3://<BUCKETNAME>/WebServer1/test.txt
5. You will now see the new data copied into your s3 bucket.


Frontend for EC2 instance


1. Go back into Webserver1 and run the following commands:
	`sudo nano /var/www/html/index.html`
2. Paste the following html into index.html   
  
```<html>
<body>

<form action="welcome.php" method="post">
Name: <input type="text" name="name"><br>
<input type="submit">
</form>

</body>
</html>
```

3. Create another file, sudo nano /var/www/html/welcome.php then paste the following code snippet.

```<?php
$name = $_POST["name"] ?? "";
$name = trim($name);

if ($name === "") {
    exit("Please enter your name.");
}

$file = "/mnt/new-volume/names.txt";

$result = file_put_contents(
    $file,
    $name . PHP_EOL,
    FILE_APPEND | LOCK_EX
);

if ($result === false) {
    http_response_code(500);
    exit("Could not save your name.");
}

echo "Welcome, " . htmlspecialchars($name, ENT_QUOTES, "UTF-8") . "!<br>";
echo "Your name has been saved to the EBS volume.";
?>
```

4. Run the following three commands, this will give apache permission to run these files on our ec2 instance.
`sudo touch /mnt/new-volume/names.txt`
`sudo chown apache:apache /mnt/new-volume/names.txt`
`sudo chmod 640 /mnt/new-volume/names.txt`

5. You should now be able to `sudo cat /mnt/new-volume/names.txt` and see your name!
6. You can now aws s3 cp `/mnt/new-volume/names.txt s3://<BUCKETNAME>/WebServer1/names.txt` to copy the data inputted by the user.

SNS Alert
1. Create a topic for the bucket.
2. Create a subscription and pick your topic, choose protocol email and enter your email, reminder to confirm this subscription in your email.
3. Open your s3 bucket and open the properties, create an event notification. Make sure your make your prefix WebServer1/ as that is the name of our current folder we're using for this project.
4. Under Event types select "All object create events", scroll to the bottom of the page and make the destination "SNS topic" and select the topic we just created.
5. Go back to your ALB and input a new name, copy your names.txt to your bucket again and await your subscription notification!

Teardown
1. Make sure you teardown the following: SNS topic, ALB, target group, empty and delete your s3 bucket, delete your block volume, finally, delete your CloudFormation stack for your ec2 instances, VPC, and security groups. Make sure it deletes everything and doesn't rollback!
